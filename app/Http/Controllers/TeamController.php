<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class TeamController extends Controller
{
    /**
     * Create a new team.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3|max:255|unique:teams,name',
        ]);

        $user = Auth::user();

        // A user can captain only one team
        if ($user->captainedTeam()->exists()) {
            return back()->withErrors(['name' => 'You are already the captain of another team.']);
        }

        DB::transaction(function () use ($request, $user) {
            // Generate a unique invite code
            do {
                $inviteCode = strtoupper(Str::random(8));
            } while (Team::where('invite_code', $inviteCode)->exists());

            $team = Team::create([
                'name' => $request->name,
                'invite_code' => $inviteCode,
                'captain_id' => $user->id,
            ]);

            // Captain is automatically a member of their own team roster
            $team->members()->attach($user->id);
        });

        return redirect('/dashboard')->with('success', "Team '{$request->name}' created successfully!");
    }

    /**
     * Join a team using an invite code.
     */
    public function join(Request $request)
    {
        $request->validate([
            'invite_code' => 'required|string|size:8',
        ]);

        $inviteCode = strtoupper($request->invite_code);
        $team = Team::where('invite_code', $inviteCode)->first();

        if (!$team) {
            return back()->withErrors(['invite_code' => 'Invalid invite code. Team not found.']);
        }

        $user = Auth::user();

        // Check if user is already a member
        if ($team->members()->where('users.id', $user->id)->exists()) {
            return back()->withErrors(['invite_code' => 'You are already a member of this team.']);
        }

        // Add user to roster
        $team->members()->attach($user->id);

        // Notify captain
        if ($team->captain_id) {
            Notification::create([
                'user_id' => $team->captain_id,
                'message' => "Player '{$user->name}' has joined your team roster.",
            ]);
        }

        return redirect('/dashboard')->with('success', "Successfully joined team '{$team->name}'!");
    }

    /**
     * Remove a member from the team roster.
     */
    public function removeMember(Team $team, User $user)
    {
        $currentUser = Auth::user();

        // Only the captain can remove members
        if ($team->captain_id !== $currentUser->id && !$currentUser->isAdmin()) {
            return back()->with('error', 'Only the team captain can manage the roster.');
        }

        // Cannot remove the captain themselves
        if ($user->id === $team->captain_id) {
            return back()->with('error', 'The team captain cannot be removed from the roster.');
        }

        DB::transaction(function () use ($team, $user) {
            // Remove user from roster
            $team->members()->detach($user->id);

            // Notify player
            Notification::create([
                'user_id' => $user->id,
                'message' => "You have been removed from the roster of team '{$team->name}'.",
            ]);

            // If team is currently registered in any active tournaments, we need to verify if the roster size falls below minimum.
            // But roster size constraints are checked at registration time.
        });

        return back()->with('success', "Player '{$user->name}' was removed from the team.");
    }

    /**
     * View team details/profile.
     */
    public function show(Team $team)
    {
        $team->load(['captain', 'members']);
        
        // Load tournament registration records and match histories
        $matches = TournamentMatch::where('team1_id', $team->id)
            ->orWhere('team2_id', $team->id)
            ->with(['tournament', 'team1', 'team2', 'winner'])
            ->latest()
            ->get();

        return view('teams.show', compact('team', 'matches'));
    }
}
