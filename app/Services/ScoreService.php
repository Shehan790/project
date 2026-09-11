<?php

namespace App\Services;

use App\Models\TournamentMatch;
use App\Models\ScoreReport;
use App\Models\Notification;
use App\Jobs\AutoFinalizeMatchJob;
use Exception;
use Illuminate\Support\Facades\DB;

class ScoreService
{
    protected $bracketService;

    public function __construct(BracketService $bracketService)
    {
        $this->bracketService = $bracketService;
    }

    /**
     * Submit a score report.
     */
    public function submitReport(TournamentMatch $match, int $reporterId, int $scoreTeam1, int $scoreTeam2): ScoreReport
    {
        if ($match->status === 'completed') {
            throw new Exception("This match is already completed.");
        }

        // Check if user is authorized to report for this match
        $user = \App\Models\User::findOrFail($reporterId);
        $isUserAuthorized = $user->isAdmin() || $user->isOrganizer();

        // If user is player/captain, check if their team is in the match
        if (!$isUserAuthorized) {
            $userTeamIds = $user->teams()->pluck('teams.id')->toArray();
            if (!in_array($match->team1_id, $userTeamIds) && !in_array($match->team2_id, $userTeamIds)) {
                throw new Exception("You are not authorized to report scores for this match.");
            }
        }

        return DB::transaction(function () use ($match, $user, $scoreTeam1, $scoreTeam2) {
            // Cancel any previous pending score reports for this match
            ScoreReport::where('match_id', $match->id)
                ->where('status', 'pending')
                ->delete();

            // If reported by admin or organizer, it is auto-confirmed!
            if ($user->isAdmin() || $user->isOrganizer()) {
                $report = ScoreReport::create([
                    'match_id' => $match->id,
                    'reporter_id' => $user->id,
                    'score_team1' => $scoreTeam1,
                    'score_team2' => $scoreTeam2,
                    'status' => 'confirmed',
                ]);

                $this->finalizeMatch($match, $scoreTeam1, $scoreTeam2);
                return $report;
            }

            // If reported by a player
            $report = ScoreReport::create([
                'match_id' => $match->id,
                'reporter_id' => $user->id,
                'score_team1' => $scoreTeam1,
                'score_team2' => $scoreTeam2,
                'status' => 'pending',
            ]);

            // Set match status to ongoing or pending confirmation
            // (We'll keep status as ongoing/scheduled but display as pending confirmation in UI)
            
            // Notify opponent captain
            $reporterTeamId = $user->teams()->whereIn('teams.id', [$match->team1_id, $match->team2_id])->first()->id;
            $opponentTeamId = ($reporterTeamId == $match->team1_id) ? $match->team2_id : $match->team1_id;

            if ($opponentTeamId) {
                $opponentTeam = \App\Models\Team::find($opponentTeamId);
                if ($opponentTeam && $opponentTeam->captain_id) {
                    Notification::create([
                        'user_id' => $opponentTeam->captain_id,
                        'message' => "Opponent team '{$user->teams()->first()->name}' has reported a score of {$scoreTeam1}-{$scoreTeam2}. Please confirm or dispute within 15 minutes.",
                    ]);
                }
            }

            // Dispatch auto-finalize job after 15 minutes
            // Delay 15 minutes (900 seconds)
            AutoFinalizeMatchJob::dispatch($report->id)->delay(now()->addMinutes(15));

            return $report;
        });
    }

    /**
     * Confirm a score report (opposing captain action).
     */
    public function confirmReport(ScoreReport $report, int $userId): void
    {
        if ($report->status !== 'pending') {
            throw new Exception("This score report is no longer pending.");
        }

        $match = $report->match;
        $user = \App\Models\User::findOrFail($userId);

        // Verify that the user belongs to the opposing team
        $reporterTeamId = $report->reporter->teams()->whereIn('teams.id', [$match->team1_id, $match->team2_id])->first()->id;
        $opposingTeamId = ($reporterTeamId == $match->team1_id) ? $match->team2_id : $match->team1_id;
        
        $userTeamIds = $user->teams()->pluck('teams.id')->toArray();
        if (!in_array($opposingTeamId, $userTeamIds) && !$user->isAdmin() && !$user->isOrganizer()) {
            throw new Exception("Only the opposing team can confirm this score report.");
        }

        DB::transaction(function () use ($report, $match) {
            $report->update(['status' => 'confirmed']);
            $this->finalizeMatch($match, $report->score_team1, $report->score_team2);
        });
    }

    /**
     * Dispute a score report.
     */
    public function disputeReport(ScoreReport $report, int $userId): void
    {
        if ($report->status !== 'pending') {
            throw new Exception("This score report is no longer pending.");
        }

        $match = $report->match;
        $user = \App\Models\User::findOrFail($userId);

        // Verify that the user belongs to the opposing team
        $reporterTeamId = $report->reporter->teams()->whereIn('teams.id', [$match->team1_id, $match->team2_id])->first()->id;
        $opposingTeamId = ($reporterTeamId == $match->team1_id) ? $match->team2_id : $match->team1_id;
        
        $userTeamIds = $user->teams()->pluck('teams.id')->toArray();
        if (!in_array($opposingTeamId, $userTeamIds) && !$user->isAdmin() && !$user->isOrganizer()) {
            throw new Exception("Only the opposing team can dispute this score report.");
        }

        DB::transaction(function () use ($report, $match, $user) {
            $report->update(['status' => 'disputed']);
            $match->update(['status' => 'disputed']);

            // Notify organizers
            $organizers = \App\Models\User::whereIn('role', ['admin', 'organizer'])->get();
            foreach ($organizers as $org) {
                Notification::create([
                    'user_id' => $org->id,
                    'message' => "Match #{$match->id} in tournament '{$match->tournament->name}' has been disputed by team captain '{$user->name}'.",
                ]);
            }
        });
    }

    /**
     * Auto-finalize a score report if no response was received after 15 minutes.
     */
    public function autoFinalize(int $reportId): void
    {
        $report = ScoreReport::find($reportId);
        if (!$report || $report->status !== 'pending') {
            return;
        }

        DB::transaction(function () use ($report) {
            $report->update(['status' => 'confirmed']);
            $this->finalizeMatch($report->match, $report->score_team1, $report->score_team2);
            
            // Notify teams
            $match = $report->match;
            $teams = [$match->team1, $match->team2];
            foreach ($teams as $team) {
                if ($team && $team->captain_id) {
                    Notification::create([
                        'user_id' => $team->captain_id,
                        'message' => "Match #{$match->id} scores were automatically finalized because no dispute was raised within 15 minutes.",
                    ]);
                }
            }
        });
    }

    /**
     * Admin/Organizer score override.
     */
    public function overrideScore(TournamentMatch $match, int $scoreTeam1, int $scoreTeam2, int $adminId): void
    {
        $admin = \App\Models\User::findOrFail($adminId);
        if (!$admin->isAdmin() && !$admin->isOrganizer()) {
            throw new Exception("Only admins or organizers can override scores.");
        }

        DB::transaction(function () use ($match, $scoreTeam1, $scoreTeam2, $admin) {
            // Delete previous reports
            ScoreReport::where('match_id', $match->id)->delete();

            // Create confirmed report from admin
            ScoreReport::create([
                'match_id' => $match->id,
                'reporter_id' => $admin->id,
                'score_team1' => $scoreTeam1,
                'score_team2' => $scoreTeam2,
                'status' => 'confirmed',
            ]);

            $this->finalizeMatch($match, $scoreTeam1, $scoreTeam2);
        });
    }

    /**
     * Helper to update winner, status, and advance the winning team in the brackets.
     */
    protected function finalizeMatch(TournamentMatch $match, int $scoreTeam1, int $scoreTeam2): void
    {
        $winnerId = ($scoreTeam1 > $scoreTeam2) ? $match->team1_id : $match->team2_id;

        if (!$winnerId) {
            throw new Exception("Could not determine a winner based on scores.");
        }

        $match->update([
            'winner_id' => $winnerId,
            'status' => 'completed',
        ]);

        // Advance winner to the next round placeholder match
        $this->bracketService->advanceWinner($match);
    }
}
