@extends('layouts.app')

@section('title', 'Player Dashboard')

@section('content')
<div class="dashboard-header">
    <div>
        <h1 style="color: var(--text-primary);">Player Dashboard</h1>
        <p style="color: var(--text-muted);">Welcome back, <span style="color: var(--neon-cyan); font-weight:bold;">{{ auth()->user()->name }}</span></p>
    </div>
    
    @if($notifications->count() > 0)
        <div class="notification-bell" onclick="markNotificationsAsRead();">
            <span style="font-size: 1.5rem;">🔔</span>
            @if($notifications->where('is_read', false)->count() > 0)
                <span class="notification-count">{{ $notifications->where('is_read', false)->count() }}</span>
            @endif
        </div>
    @endif
</div>

<!-- Notifications Drawer (if any exist) -->
@if($notifications->count() > 0)
    <div class="notifications-panel">
        <h3 class="brand-name" style="font-size: 0.9rem; color: var(--neon-purple); margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
            System Notifications
            @if($notifications->where('is_read', false)->count() > 0)
                <button onclick="markNotificationsAsRead();" class="btn btn-secondary btn-small" style="font-size: 0.65rem; padding: 2px 6px;">Mark all read</button>
            @endif
        </h3>
        @foreach($notifications as $n)
            <div class="notification-item {{ !$n->is_read ? 'unread' : '' }}">
                <span style="color: var(--text-muted); font-size: 0.75rem;">[{{ $n->created_at->format('H:i') }}]</span> {{ $n->message }}
            </div>
        @endforeach
    </div>
@endif

<div class="grid grid-2">
    
    <!-- Team Roster Section -->
    <div>
        @if($captainedTeam)
            <div class="card">
                <div class="card-title">
                    <span>Team: {{ $captainedTeam->name }}</span>
                    <span class="badge badge-open">Captain</span>
                </div>
                <p style="margin-bottom: 15px; color: var(--text-muted); font-size: 0.9rem;">
                    Roster Invite Code: <strong style="color: var(--neon-cyan); font-family: 'Orbitron'; font-size: 1.1rem; letter-spacing: 0.05em;">{{ $captainedTeam->invite_code }}</strong>
                </p>
                
                <h4 style="color: var(--text-primary); font-size: 0.95rem; margin-bottom: 10px; border-bottom: 1px solid var(--border-color); padding-bottom: 5px;">Roster Members</h4>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($captainedTeam->members as $member)
                                <tr>
                                    <td>{{ $member->name }} ({{ $member->username }})</td>
                                    <td>
                                        @if($member->id === $captainedTeam->captain_id)
                                            <span style="color: var(--neon-gold); font-weight:bold;">Captain</span>
                                        @else
                                            Player
                                        @endif
                                    </td>
                                    <td>
                                        @if($member->id !== $captainedTeam->captain_id)
                                            <form action="/teams/{{ $captainedTeam->id }}/members/{{ $member->id }}" method="POST" onsubmit="return confirm('Kick this player from the team?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-small">Kick</button>
                                            </form>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 20px; display:flex; gap:10px;">
                    <a href="{{ route('teams.show', $captainedTeam->id) }}" class="btn btn-secondary btn-small w-full">View Team Profile</a>
                </div>
            </div>
        @elseif($joinedTeams->count() > 0)
            @foreach($joinedTeams as $team)
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-title">
                        <span>Team: {{ $team->name }}</span>
                        <span class="badge badge-active">Member</span>
                    </div>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 15px;">Captain: {{ $team->captain->name }}</p>
                    <a href="{{ route('teams.show', $team->id) }}" class="btn btn-secondary btn-small w-full">View Team Profile</a>
                </div>
            @endforeach
        @else
            <!-- Roster up: Forms to create or join team -->
            <div class="card">
                <h3 class="card-title" style="color: var(--neon-cyan);">Roster Up</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 25px;">To enter tournaments, you must either create a new team as a captain, or join an existing team roster via an invite code.</p>
                
                <!-- Create Team Form -->
                <form action="/teams" method="POST" style="margin-bottom: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 25px;">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="team_name">Create a Team</label>
                        <input type="text" name="name" id="team_name" class="form-control" required placeholder="Enter a unique team name">
                    </div>
                    <button type="submit" class="btn btn-primary btn-small">Register Team</button>
                </form>

                <!-- Join Team Form -->
                <form action="/teams/join" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="invite_code">Join with Invite Code</label>
                        <input type="text" name="invite_code" id="invite_code" class="form-control" size="8" required placeholder="e.g. AB12CD34">
                    </div>
                    <button type="submit" class="btn btn-secondary btn-small">Join Team</button>
                </form>
            </div>
        @endif
    </div>
    
    <!-- Matches & Tournaments Section -->
    <div>
        <!-- Match Schedule & score reports -->
        <div class="card">
            <h3 class="card-title" style="color: var(--neon-purple);">Active Match Schedule</h3>
            
            @if($activeMatches->count() > 0)
                @foreach($activeMatches as $match)
                    <div style="background-color: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 6px; padding: 15px; margin-bottom: 15px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px;">
                            <span class="badge badge-active" style="font-size: 0.65rem;">{{ $match->tournament->name }}</span>
                            <span style="color: var(--text-muted); font-size: 0.8rem;">Round {{ $match->round_number }}</span>
                        </div>
                        
                        <div class="text-center" style="margin: 15px 0; display:flex; justify-content:space-around; align-items:center;">
                            <strong style="font-family:'Orbitron'; font-size: 1.1rem; color: {{ $match->winner_id == $match->team1_id ? 'var(--neon-cyan)' : 'var(--text-primary)' }}">{{ $match->team1->name }}</strong>
                            <span style="color: var(--neon-purple); font-weight:bold; font-family:'Orbitron';">VS</span>
                            <strong style="font-family:'Orbitron'; font-size: 1.1rem; color: {{ $match->winner_id == $match->team2_id ? 'var(--neon-cyan)' : 'var(--text-primary)' }}">{{ $match->team2->name }}</strong>
                        </div>

                        <!-- Verification workflow / forms -->
                        @php
                            $pendingReport = $match->scoreReports()->where('status', 'pending')->first();
                            $hasSubmitted = $pendingReport && $pendingReport->reporter_id === auth()->id();
                            $requiresVerification = $pendingReport && $pendingReport->reporter_id !== auth()->id();
                        @endphp

                        @if($match->status === 'disputed')
                            <div class="badge badge-disputed text-center w-full" style="padding: 10px; display:block;">Disputed. Waiting for administrator override.</div>
                        @elseif($requiresVerification)
                            <div style="background-color: rgba(255, 215, 0, 0.1); border: 1px dashed var(--neon-gold); border-radius: 4px; padding: 10px; margin-top: 10px;">
                                <p style="font-size: 0.85rem; color: var(--neon-gold); margin-bottom: 10px; text-align:center;">
                                    Opponent reported score: <strong>{{ $pendingReport->score_team1 }} - {{ $pendingReport->score_team2 }}</strong>. Confirm or dispute!
                                </p>
                                <div style="display:flex; gap:10px;">
                                    <form action="/score-reports/{{ $pendingReport->id }}/confirm" method="POST" style="flex:1;">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-small w-full">Confirm (Finalize)</button>
                                    </form>
                                    <form action="/score-reports/{{ $pendingReport->id }}/dispute" method="POST" style="flex:1;">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-small w-full">Dispute</button>
                                    </form>
                                </div>
                            </div>
                        @elseif($hasSubmitted)
                            <div class="badge badge-pending text-center w-full" style="padding: 10px; display:block;">Report submitted ({{ $pendingReport->score_team1 }}-{{ $pendingReport->score_team2 }}). Waiting for opponent.</div>
                        @else
                            <!-- Show Score submission form (only captains can submit score) -->
                            @if($captainedTeam && ($captainedTeam->id === $match->team1_id || $captainedTeam->id === $match->team2_id))
                                <form action="/matches/{{ $match->id }}/report" method="POST" style="margin-top: 15px; border-top: 1px dashed var(--border-color); padding-top: 15px;">
                                    @csrf
                                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 10px; text-align:center;">REPORT SCORE</p>
                                    <div style="display:flex; justify-content:center; align-items:center; gap: 10px; margin-bottom: 10px;">
                                        <input type="number" name="score_team1" class="form-control text-center" style="width: 70px;" min="0" required placeholder="0">
                                        <span style="color:var(--text-muted); font-weight:bold;">:</span>
                                        <input type="number" name="score_team2" class="form-control text-center" style="width: 70px;" min="0" required placeholder="0">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-small w-full">Submit Scores</button>
                                </form>
                            @else
                                <p style="text-align:center; font-size: 0.8rem; color:var(--text-muted); margin-top: 10px;">Roster member: only team captains can report scores.</p>
                            @endif
                        @endif
                    </div>
                @endforeach
            @else
                <p style="color: var(--text-muted); font-size: 0.9rem; text-align:center; padding: 20px 0;">No active matches scheduled for your team roster.</p>
            @endif
        </div>

        <!-- Registered Tournaments -->
        <div class="card mt-4">
            <h3 class="card-title" style="color: var(--neon-cyan);">Registered Tournaments</h3>
            @if($registeredTournaments->count() > 0)
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Bracket</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($registeredTournaments as $t)
                                <tr>
                                    <td>{{ $t->name }} ({{ $t->game_title }})</td>
                                    <td>
                                        <span class="badge badge-{{ $t->status }}">{{ $t->status }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('tournaments.show', $t->id) }}" class="btn btn-secondary btn-small">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p style="color: var(--text-muted); font-size: 0.9rem; text-align:center; padding: 20px 0;">You have not registered for any tournaments yet.</p>
            @endif
        </div>
    </div>
</div>
@endsection
