<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TournamentMatch;
use App\Models\Tournament;
use App\Services\ScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class AdminController extends Controller
{
    protected $scoreService;

    public function __construct(ScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    /**
     * View users management page.
     */
    public function usersIndex()
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403);
        }

        $users = User::orderBy('username')->get();
        return view('admin.users', compact('users'));
    }

    /**
     * Update user role.
     */
    public function updateUserRole(Request $request, User $targetUser)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'role' => 'required|in:admin,organizer,player',
        ]);

        // Prevent self-demotion
        if ($targetUser->id === Auth::id() && $request->role !== 'admin') {
            return back()->with('error', 'You cannot demote yourself from Admin.');
        }

        $oldRole = $targetUser->role;
        $targetUser->update(['role' => $request->role]);

        Log::info("Admin #{" . Auth::id() . "} changed role of User #{$targetUser->id} ({$targetUser->username}) from {$oldRole} to {$request->role}");

        return back()->with('success', "User '{$targetUser->username}' role updated to '{$request->role}'.");
    }

    /**
     * Delete a user account (admin only).
     */
    public function destroyUser(Request $request, User $targetUser)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        // Prevent deleting self
        if ($targetUser->id === Auth::id()) {
            return back()->with('error', 'You cannot remove your own account.');
        }

        // Prevent removing other admins for safety
        if ($targetUser->isAdmin()) {
            return back()->with('error', 'Cannot remove another admin user.');
        }

        try {
            $username = $targetUser->username;
            $targetUser->delete();
            Log::info("Admin #{" . Auth::id() . "} deleted User #{$targetUser->id} ({$username})");

            return back()->with('success', "User '{$username}' has been removed.");
        } catch (Exception $e) {
            Log::error('Failed to delete user: ' . $e->getMessage());
            return back()->with('error', 'Failed to remove user.');
        }
    }

    /**
     * Override match score (Dispute Resolution).
     */
    public function resolveDispute(Request $request, TournamentMatch $match)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isOrganizer()) {
            abort(403);
        }

        $request->validate([
            'score_team1' => 'required|integer|min:0',
            'score_team2' => 'required|integer|min:0',
        ]);

        try {
            $this->scoreService->overrideScore(
                $match,
                $request->score_team1,
                $request->score_team2,
                $user->id
            );

            Log::info("Organizer #{$user->id} resolved dispute for Match #{$match->id} with score {$request->score_team1}-{$request->score_team2}");

            return redirect('/dashboard')->with('success', "Dispute resolved. Match score set to {$request->score_team1}-{$request->score_team2}.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Export tournament brackets and results to CSV format.
     */
    public function exportTournamentCSV(Tournament $tournament)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isOrganizer()) {
            abort(403);
        }

        $matches = $tournament->matches()
            ->with(['team1', 'team2', 'winner'])
            ->orderBy('round_number')
            ->orderBy('match_number_in_round')
            ->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=tournament_{$tournament->id}_results.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($tournament, $matches) {
            $file = fopen('php://output', 'w');
            
            // CSV metadata
            fputcsv($file, ["Tournament Name", $tournament->name]);
            fputcsv($file, ["Game Title", $tournament->game_title]);
            fputcsv($file, ["Status", $tournament->status]);
            fputcsv($file, ["Start Date", $tournament->start_date->toDateTimeString()]);
            fputcsv($file, []); // Blank line
            
            // Column headers
            fputcsv($file, ["Match ID", "Round Number", "Match # in Round", "Team 1", "Team 2", "Winner", "Status"]);

            foreach ($matches as $match) {
                fputcsv($file, [
                    $match->id,
                    $match->round_number,
                    $match->match_number_in_round + 1,
                    $match->team1 ? $match->team1->name : 'Bye/Pending',
                    $match->team2 ? $match->team2->name : 'Bye/Pending',
                    $match->winner ? $match->winner->name : 'None',
                    $match->status
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
