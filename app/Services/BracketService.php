<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\Team;
use Exception;
use Illuminate\Support\Facades\DB;

class BracketService
{
    /**
     * Generate the single-elimination bracket for a tournament.
     */
    public function generateBracket(Tournament $tournament): void
    {
        if ($tournament->status !== 'open') {
            throw new Exception("Tournament must be in open registration to start.");
        }

        // Preserve registration order so team1/team2 positions remain stable for score reporting.
        $teams = $tournament->teams()->get();
        $teamCount = $teams->count();

        if ($teamCount < 2) {
            throw new Exception("A tournament requires at least 2 teams to generate a bracket.");
        }

        DB::transaction(function () use ($tournament, $teams, $teamCount) {
            // 1. Calculate nearest power of 2 >= $teamCount
            $power = 1;
            while ($power < $teamCount) {
                $power *= 2;
            }

            $totalRounds = log($power, 2);
            $byesCount = $power - $teamCount;

            // Prepare team list padded with nulls for byes
            $bracketTeams = [];
            
            // Distribute byes evenly
            // We alternate putting actual teams and byes
            $byePositions = $this->calculateByePositions($power, $byesCount);

            $teamIndex = 0;
            for ($i = 0; $i < $power; $i++) {
                if (in_array($i, $byePositions)) {
                    $bracketTeams[$i] = null;
                } else {
                    $bracketTeams[$i] = $teams[$teamIndex++];
                }
            }

            // 2. Generate Round 1 Matches
            $round1MatchCount = $power / 2;
            $round1Matches = [];

            for ($m = 0; $m < $round1MatchCount; $m++) {
                $team1 = $bracketTeams[$m * 2];
                $team2 = $bracketTeams[$m * 2 + 1];

                // Create the Match
                $match = TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'team1_id' => $team1 ? $team1->id : null,
                    'team2_id' => $team2 ? $team2->id : null,
                    'winner_id' => null,
                    'round_number' => 1,
                    'match_number_in_round' => $m,
                    'status' => 'scheduled',
                ]);

                $round1Matches[] = $match;
            }

            // 3. Create Placeholder Matches for subsequent rounds
            $currentRoundMatchCount = $round1MatchCount / 2;
            for ($r = 2; $r <= $totalRounds; $r++) {
                for ($m = 0; $m < $currentRoundMatchCount; $m++) {
                    TournamentMatch::create([
                        'tournament_id' => $tournament->id,
                        'team1_id' => null,
                        'team2_id' => null,
                        'winner_id' => null,
                        'round_number' => $r,
                        'match_number_in_round' => $m,
                        'status' => 'scheduled',
                    ]);
                }
                $currentRoundMatchCount /= 2;
            }

            // 4. Resolve Byes immediately
            foreach ($round1Matches as $match) {
                if ($match->team1_id === null && $match->team2_id === null) {
                    // Double bye - shouldn't happen under clean distribution, but skip
                    continue;
                }

                if ($match->team1_id === null || $match->team2_id === null) {
                    $winnerId = $match->team1_id ?? $match->team2_id;
                    $match->update([
                        'winner_id' => $winnerId,
                        'status' => 'completed',
                    ]);

                    // Advance the winner to Round 2
                    $this->advanceWinner($match);
                }
            }

            // Update tournament status
            $tournament->update(['status' => 'active']);
        });
    }

    /**
     * Advance a winner of a match to the next round placeholder match.
     */
    public function advanceWinner(TournamentMatch $match): void
    {
        if (!$match->winner_id) {
            return;
        }

        $nextRound = $match->round_number + 1;
        $nextMatchNumber = floor($match->match_number_in_round / 2);
        $isTeam1 = ($match->match_number_in_round % 2 == 0);

        // Find the placeholder match in the next round
        $nextMatch = TournamentMatch::where('tournament_id', $match->tournament_id)
            ->where('round_number', $nextRound)
            ->where('match_number_in_round', $nextMatchNumber)
            ->first();

        if ($nextMatch) {
            if ($isTeam1) {
                $nextMatch->update(['team1_id' => $match->winner_id]);
            } else {
                $nextMatch->update(['team2_id' => $match->winner_id]);
            }

            // If the next match now has only one team, and it's the final round or there's a bye?
            // Usually next rounds won't have byes, unless it's a progression from a bye.
            // If the next match gets populated and the opponent is somehow a bye, it will handle it.
        } else {
            // No next match found - this means the current match was the tournament final!
            $match->tournament->update(['status' => 'completed']);
        }
    }

    /**
     * Compute indices where byes should be inserted to distribute them evenly.
     */
    private function calculateByePositions(int $slots, int $byesCount): array
    {
        if ($byesCount <= 0) {
            return [];
        }

        $positions = [];
        // A simple algorithm to space out the byes
        // e.g. if we have 8 slots and 3 byes, we space them
        $step = $slots / $byesCount;
        for ($i = 0; $i < $byesCount; $i++) {
            // Place byes on odd slot indices so they pair with actual teams
            $pos = floor($i * $step) * 2 + 1;
            $positions[] = (int)($pos % $slots);
        }

        return array_unique($positions);
    }
}
