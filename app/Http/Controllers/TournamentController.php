<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\Team;
use App\Models\TournamentMatch;
use App\Models\Notification;
use App\Services\BracketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class TournamentController extends Controller
{
    protected $bracketService;

    public function __construct(BracketService $bracketService)
    {
        $this->bracketService = $bracketService;
    }

    /**
     * Display a listing of tournaments.
     */
    public function index()
    {
        $tournaments = Tournament::latest()->get();
        return view('tournaments.index', compact('tournaments'));
    }

    /**
     * Store a newly created tournament.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isOrganizer()) {
            return back()->with('error', 'Only organizers and admins can create tournaments.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'game_title' => 'required|string|max:255',
            'start_date' => 'required|date|after:now',
            'registration_deadline' => 'required|date|before:start_date|after:now',
            'max_participants' => 'required|integer|min:2|max:128',
            'description' => 'nullable|string',
        ]);

        Tournament::create([
            'name' => $request->name,
            'game_title' => $request->game_title,
            'start_date' => $request->start_date,
            'registration_deadline' => $request->registration_deadline,
            'max_participants' => $request->max_participants,
            'description' => $request->description,
            'status' => 'open', // Starts as open for registration
        ]);

        return redirect('/dashboard')->with('success', 'Tournament created successfully! Registration is now open.');
    }

    /**
     * Display tournament details, team list, and the visual bracket.
     */
    public function show(Tournament $tournament)
    {
        $tournament->load(['teams']);

        // Load all matches grouped by round
        $matches = $tournament->matches()
            ->with(['team1', 'team2', 'winner'])
            ->orderBy('round_number')
            ->orderBy('match_number_in_round')
            ->get()
            ->groupBy('round_number');

        // Check if the current user has a team they can register
        $myCaptainedTeam = Auth::check() ? Auth::user()->captainedTeam : null;
        $isRegistered = false;

        if ($myCaptainedTeam && $tournament->teams->contains($myCaptainedTeam->id)) {
            $isRegistered = true;
        }

        return view('tournaments.show', compact('tournament', 'matches', 'myCaptainedTeam', 'isRegistered'));
    }

    /**
     * Register a team for the tournament.
     */
    public function register(Request $request, Tournament $tournament)
    {
        $user = Auth::user();
        $team = $user->captainedTeam;

        if (!$team) {
            return back()->with('error', 'Only team captains can register teams for tournaments.');
        }

        if ($tournament->status !== 'open') {
            return back()->with('error', 'Registration for this tournament is closed.');
        }

        if ($tournament->registration_deadline->isPast()) {
            return back()->with('error', 'The registration deadline has passed.');
        }

        // Check if roster size meets minimum tournament guidelines (e.g. at least 3 players)
        $memberCount = $team->members()->count();
        if ($memberCount < 3 || $memberCount > 5) {
            return back()->with('error', 'Your roster size must be between 3 and 5 players to register.');
        }

        // Check if maximum participant limit is reached
        if ($tournament->teams()->count() >= $tournament->max_participants) {
            return back()->with('error', 'The tournament has reached its maximum participant limit.');
        }

        // Check duplicate players across other registered teams for this same tournament
        $tournamentPlayerIds = DB::table('tournament_team')
            ->join('team_user', 'tournament_team.team_id', '=', 'team_user.team_id')
            ->where('tournament_team.tournament_id', $tournament->id)
            ->pluck('team_user.user_id')
            ->toArray();

        $myTeamPlayerIds = $team->members()->pluck('users.id')->toArray();

        $duplicates = array_intersect($tournamentPlayerIds, $myTeamPlayerIds);
        if (!empty($duplicates)) {
            $duplicateNames = \App\Models\User::whereIn('id', $duplicates)->pluck('name')->toArray();
            return back()->with('error', 'Registration failed. The following players are already registered on other teams in this tournament: ' . implode(', ', $duplicateNames));
        }

        DB::transaction(function () use ($tournament, $team) {
            $tournament->teams()->attach($team->id);

            // If max participants reached, automatically update status to closed
            if ($tournament->teams()->count() >= $tournament->max_participants) {
                $tournament->update(['status' => 'pending']); // Closed, waiting to start
            }
        });

        return back()->with('success', "Team '{$team->name}' registered successfully!");
    }

    /**
     * Start the tournament and generate matches.
     */
    public function start(Tournament $tournament)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isOrganizer()) {
            return back()->with('error', 'Only organizers can start tournaments.');
        }

        try {
            // Temporarily set tournament status to open if it was auto-closed/pending
            if ($tournament->status === 'pending') {
                $tournament->status = 'open';
            }

            $this->bracketService->generateBracket($tournament);
            
            // Notify all registered captains
            foreach ($tournament->teams as $team) {
                if ($team->captain_id) {
                    Notification::create([
                        'user_id' => $team->captain_id,
                        'message' => "Tournament '{$tournament->name}' has started! Brackets have been generated and matches scheduled.",
                    ]);
                }
            }

            return back()->with('success', 'Tournament started successfully! Brackets are live.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a tournament and all related matches and registrations.
     */
    public function destroy(Tournament $tournament)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403);
        }

        $tournament->delete();

        return redirect('/dashboard')->with('success', 'Tournament deleted successfully.');
    }

    /**
     * JSON bracket api endpoint for real-time client-side polling.
     */
    public function getBracketData(Tournament $tournament)
    {
        $matches = $tournament->matches()
            ->with(['team1:id,name', 'team2:id,name', 'winner:id,name', 'scoreReports.reporter:id,name'])
            ->orderBy('round_number')
            ->orderBy('match_number_in_round')
            ->get();

        return response()->json([
            'status' => $tournament->status,
            'matches' => $matches
        ]);
    }
}
