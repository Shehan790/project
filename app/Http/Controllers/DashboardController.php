<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\Team;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. Common data
        $notifications = $user->notifications()->take(5)->get();

        // 2. Load role-specific dashboards
        if ($user->isAdmin()) {
            return $this->adminDashboard($notifications);
        } elseif ($user->isOrganizer()) {
            return $this->organizerDashboard($notifications);
        } else {
            return $this->playerDashboard($user, $notifications);
        }
    }

    protected function adminDashboard($notifications)
    {
        $stats = [
            'total_users' => User::count(),
            'total_teams' => Team::count(),
            'total_tournaments' => Tournament::where('status', '!=', 'completed')->count(),
            'pending_disputes' => TournamentMatch::where('status', 'disputed')->count(),
        ];

        $disputedMatches = TournamentMatch::where('status', 'disputed')
            ->with(['tournament', 'team1', 'team2', 'scoreReports'])
            ->get();

        $tournaments = Tournament::latest()->get();

        // Read system logs or simulate audit logs
        $auditLogs = [];
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $lines = file($logFile);
            $count = count($lines);
            // Get last 15 lines containing user events
            $filteredLines = array_filter($lines, function ($line) {
                return str_contains($line, 'User logged') || 
                       str_contains($line, 'registered') ||
                       str_contains($line, 'disputed') ||
                       str_contains($line, 'Tournament') ||
                       str_contains($line, 'Match');
            });
            $auditLogs = array_slice(array_reverse($filteredLines), 0, 10);
        }

        return view('dashboard.admin', compact('stats', 'disputedMatches', 'auditLogs', 'notifications', 'tournaments'));
    }

    protected function organizerDashboard($notifications)
    {
        $tournaments = Tournament::latest()->get();
        $disputedMatches = TournamentMatch::where('status', 'disputed')
            ->with(['tournament', 'team1', 'team2', 'scoreReports'])
            ->get();

        $stats = [
            'active_count' => Tournament::where('status', 'active')->count(),
            'open_count' => Tournament::where('status', 'open')->count(),
            'disputed_count' => $disputedMatches->count(),
        ];

        return view('dashboard.organizer', compact('tournaments', 'disputedMatches', 'stats', 'notifications'));
    }

    protected function playerDashboard($user, $notifications)
    {
        // Find team captained
        $captainedTeam = $user->captainedTeam;
        
        // Find teams joined
        $joinedTeams = $user->teams;

        // Collect all tournament IDs this player's teams are registered in
        $registeredTournamentIds = [];
        $allMyTeamIds = [];
        
        if ($captainedTeam) {
            $allMyTeamIds[] = $captainedTeam->id;
        }
        foreach ($joinedTeams as $jt) {
            $allMyTeamIds[] = $jt->id;
        }
        $allMyTeamIds = array_unique($allMyTeamIds);

        // Find matches for these teams
        $activeMatches = TournamentMatch::whereIn('status', ['scheduled', 'ongoing', 'disputed'])
            ->where(function ($query) use ($allMyTeamIds) {
                $query->whereIn('team1_id', $allMyTeamIds)
                      ->orWhereIn('team2_id', $allMyTeamIds);
            })
            ->with(['tournament', 'team1', 'team2', 'scoreReports'])
            ->get();

        // Get registered tournaments
        $registeredTournaments = Tournament::whereHas('teams', function ($query) use ($allMyTeamIds) {
            $query->whereIn('teams.id', $allMyTeamIds);
        })->get();

        return view('dashboard.player', compact('captainedTeam', 'joinedTeams', 'activeMatches', 'registeredTournaments', 'notifications'));
    }

    public function markNotificationsRead()
    {
        Auth::user()->notifications()->where('is_read', false)->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }
}
