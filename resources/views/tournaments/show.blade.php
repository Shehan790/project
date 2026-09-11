@extends('layouts.app')

@section('title', $tournament->name)

@section('content')
<div class="dashboard-header">
    <div>
        <h1 style="color: var(--text-primary);">{{ $tournament->name }}</h1>
        <p style="color: var(--text-muted);">Game: <span style="color: var(--neon-purple); font-weight:bold; font-family:'Orbitron';">{{ $tournament->game_title }}</span></p>
    </div>
    <div>
        <span class="badge badge-{{ $tournament->status }}" style="font-size: 0.9rem; padding: 6px 12px;">Status: {{ $tournament->status }}</span>
    </div>
</div>

<div class="grid grid-3" style="grid-template-columns: 1fr 2fr; gap:30px;">
    <!-- Sidebar: Tournament details and participant list -->
    <div>
        <div class="card">
            <h3 class="card-title" style="color: var(--neon-cyan);">Tournament Info</h3>
            
            <p style="color: var(--text-primary); font-size: 0.95rem; margin-bottom: 20px;">
                {{ $tournament->description ?: 'No rules or description provided for this tournament.' }}
            </p>

            <div style="border-top: 1px solid var(--border-color); padding-top: 15px; margin-bottom: 20px; font-size: 0.85rem; color: var(--text-muted);">
                <div style="display:flex; justify-content:space-between; margin-bottom: 8px;">
                    <span>Max Teams:</span>
                    <strong class="numeric" style="color: var(--text-primary);">{{ $tournament->max_participants }}</strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom: 8px;">
                    <span>Registered Teams:</span>
                    <strong class="numeric" style="color: var(--text-primary);">{{ $tournament->teams->count() }}</strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom: 8px;">
                    <span>Reg Deadline:</span>
                    <strong style="color: var(--text-primary);">{{ $tournament->registration_deadline->format('M d, H:i') }}</strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span>Tournament Starts:</span>
                    <strong style="color: var(--text-primary);">{{ $tournament->start_date->format('M d, H:i') }}</strong>
                </div>
            </div>

            <!-- Registration Controls -->
            @auth
                @if($tournament->status === 'open')
                    @if($isRegistered)
                        <div class="badge badge-completed text-center w-full" style="padding: 10px; display:block;">Your Team is Registered</div>
                    @elseif($myCaptainedTeam)
                        <form action="/tournaments/{{ $tournament->id }}/register" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary w-full">Register Team '{{ $myCaptainedTeam->name }}'</button>
                        </form>
                    @else
                        <div class="text-center" style="font-size:0.8rem; color:var(--text-muted);">
                            You must captain a team to register. Roster up on your <a href="/dashboard">Dashboard</a> first.
                        </div>
                    @endif
                @else
                    <div class="badge badge-pending text-center w-full" style="padding: 10px; display:block; background-color:rgba(255,255,255,0.05); border:1px solid var(--border-color); color:var(--text-muted);">Registration Closed</div>
                @endif
            @else
                <div class="text-center">
                    <a href="/login" class="btn btn-primary w-full">Login to Register</a>
                </div>
            @endauth
        </div>

        <!-- Participants List -->
        <div class="card mt-4">
            <h3 class="card-title" style="color: var(--neon-purple);">Registered Teams</h3>
            @if($tournament->teams->count() > 0)
                <ul style="list-style:none; padding: 0;">
                    @foreach($tournament->teams as $team)
                        <li style="padding: 10px 0; border-bottom: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                            <a href="{{ route('teams.show', $team->id) }}" style="font-weight: 500; font-family:'Orbitron';">{{ $team->name }}</a>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">Captain: {{ $team->captain->name }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p style="color: var(--text-muted); font-size: 0.9rem; text-align:center; padding: 20px 0;">No teams registered yet.</p>
            @endif
        </div>
    </div>

    <!-- Main Bracket Panel -->
    <div>
        <div class="card" style="height: 100%;">
            <div class="card-title">
                <span style="color: var(--neon-cyan);">Tournament Bracket</span>
                @if($tournament->status === 'active')
                    <span style="font-size:0.75rem; color: var(--text-muted); text-transform:uppercase; animation: pulse 2s infinite;" id="live-indicator">● LIVE UPDATING</span>
                @endif
            </div>

            @if($matches->count() > 0)
                <div class="bracket-wrapper" id="bracket-root">
                    @foreach($matches as $roundNumber => $roundMatches)
                        <div class="bracket-round">
                            <div class="bracket-round-title">
                                @if($loop->last)
                                    FINALS
                                @elseif($loop->remaining === 1)
                                    SEMIFINALS
                                @elseif($loop->remaining === 2)
                                    QUARTERFINALS
                                @else
                                    ROUND {{ $roundNumber }}
                                @endif
                            </div>
                            
                            @foreach($roundMatches as $match)
                                <div class="bracket-match" data-match-id="{{ $match->id }}">
                                    <!-- Team 1 -->
                                    <div class="bracket-match-team {{ ($match->winner_id && $match->winner_id === $match->team1_id) ? 'winner' : '' }}" data-team-id="{{ $match->team1_id }}">
                                        <span class="team-name">{{ $match->team1 ? $match->team1->name : ($match->round_number === 1 ? 'Bye' : 'Pending') }}</span>
                                        <span class="bracket-score">{{ $match->status === 'completed' && $match->scoreReports()->where('status', 'confirmed')->first() ? $match->scoreReports()->where('status', 'confirmed')->first()->score_team1 : '-' }}</span>
                                    </div>
                                    
                                    <!-- Team 2 -->
                                    <div class="bracket-match-team {{ ($match->winner_id && $match->winner_id === $match->team2_id) ? 'winner' : '' }}" data-team-id="{{ $match->team2_id }}">
                                        <span class="team-name">{{ $match->team2 ? $match->team2->name : ($match->round_number === 1 ? 'Bye' : 'Pending') }}</span>
                                        <span class="bracket-score">{{ $match->status === 'completed' && $match->scoreReports()->where('status', 'confirmed')->first() ? $match->scoreReports()->where('status', 'confirmed')->first()->score_team2 : '-' }}</span>
                                    </div>

                                    @auth
                                        @php
                                            $currentUser = Auth::user();
                                            $captainedTeam = $currentUser ? $currentUser->captainedTeam : null;
                                            $isTeamCaptainForMatch = $captainedTeam && ($captainedTeam->id === $match->team1_id || $captainedTeam->id === $match->team2_id);
                                            $canManageScores = $currentUser && ($currentUser->isAdmin() || $currentUser->isOrganizer() || $isTeamCaptainForMatch);
                                            $pendingReport = $match->scoreReports()->where('status', 'pending')->first();
                                        @endphp

                                        @if($canManageScores && $match->status !== 'completed' && $match->status !== 'disputed')
                                            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--border-color);">
                                                @if($pendingReport)
                                                    @if($pendingReport->reporter_id === $currentUser->id)
                                                        <div class="badge badge-pending" style="display:block; text-align:center; padding:6px; font-size:0.72rem;">Score submitted. Waiting for opponent confirmation.</div>
                                                    @else
                                                        <div style="font-size:0.72rem; color:var(--text-muted); text-align:center; margin-bottom:8px;">Opponent submitted a score. Confirm or dispute it.</div>
                                                        <div style="display:flex; gap:8px;">
                                                            <form action="/score-reports/{{ $pendingReport->id }}/confirm" method="POST" style="flex:1;">
                                                                @csrf
                                                                <button type="submit" class="btn btn-primary" style="width:100%; padding:8px 10px; font-size:0.75rem;">Confirm</button>
                                                            </form>
                                                            <form action="/score-reports/{{ $pendingReport->id }}/dispute" method="POST" style="flex:1;">
                                                                @csrf
                                                                <button type="submit" class="btn btn-secondary" style="width:100%; padding:8px 10px; font-size:0.75rem;">Dispute</button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                @else
                                                    <div style="font-size:0.72rem; color:var(--text-muted); text-align:center; margin-bottom:8px;">Report Score</div>
                                                    <form action="/matches/{{ $match->id }}/report" method="POST">
                                                        @csrf
                                                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                                                            <input type="number" name="score_team1" min="0" required placeholder="{{ $match->team1 ? $match->team1->name : 'Team 1' }}" style="flex:1; padding:6px 8px; border:1px solid var(--border-color); background:rgba(255,255,255,0.03); color:var(--text-primary); border-radius:6px;">
                                                            <span style="color:var(--text-muted);">-</span>
                                                            <input type="number" name="score_team2" min="0" required placeholder="{{ $match->team2 ? $match->team2->name : 'Team 2' }}" style="flex:1; padding:6px 8px; border:1px solid var(--border-color); background:rgba(255,255,255,0.03); color:var(--text-primary); border-radius:6px;">
                                                        </div>
                                                        <button type="submit" class="btn btn-primary" style="width:100%; padding:8px 10px; font-size:0.75rem;">Submit Score</button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endif
                                    @endauth
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @else
                <p style="color: var(--text-muted); font-size: 0.95rem; text-align:center; padding: 100px 0;">Brackets will be generated when the organizer starts the tournament.</p>
            @endif
        </div>
    </div>
</div>

<style>
@keyframes pulse {
    0% { opacity: 0.4; }
    50% { opacity: 1; }
    100% { opacity: 0.4; }
}
</style>
@endsection

@section('scripts')
@if($tournament->status === 'active')
<script>
    // Live bracket polling (R-PERF-4 requirement)
    setInterval(() => {
        fetch('/tournaments/{{ $tournament->id }}/bracket/data')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'completed') {
                document.getElementById('live-indicator').innerText = 'COMPLETED';
                document.getElementById('live-indicator').style.animation = 'none';
                document.getElementById('live-indicator').style.color = 'var(--neon-green)';
            }
            
            data.matches.forEach(match => {
                const matchEl = document.querySelector(`.bracket-match[data-match-id="${match.id}"]`);
                if (matchEl) {
                    const team1El = matchEl.querySelector('.bracket-match-team:first-child');
                    const team2El = matchEl.querySelector('.bracket-match-team:last-child');
                    
                    // Update team 1 name and score
                    const t1Name = match.team1 ? match.team1.name : (match.round_number === 1 ? 'Bye' : 'Pending');
                    team1El.querySelector('.team-name').innerText = t1Name;
                    team1El.setAttribute('data-team-id', match.team1_id || '');
                    
                    // Update team 2 name and score
                    const t2Name = match.team2 ? match.team2.name : (match.round_number === 1 ? 'Bye' : 'Pending');
                    team2El.querySelector('.team-name').innerText = t2Name;
                    team2El.setAttribute('data-team-id', match.team2_id || '');
                    
                    // Check if score is available
                    let score1 = '-';
                    let score2 = '-';
                    
                    // Look through reports to find a confirmed one
                    const confirmedReport = match.score_reports && match.score_reports.find(r => r.status === 'confirmed');
                    if (confirmedReport) {
                        score1 = confirmedReport.score_team1;
                        score2 = confirmedReport.score_team2;
                    }
                    
                    team1El.querySelector('.bracket-score').innerText = score1;
                    team2El.querySelector('.bracket-score').innerText = score2;
                    
                    // Update winners highlight
                    team1El.classList.remove('winner');
                    team2El.classList.remove('winner');
                    if (match.winner_id) {
                        if (match.winner_id == match.team1_id) {
                            team1El.classList.add('winner');
                        } else if (match.winner_id == match.team2_id) {
                            team2El.classList.add('winner');
                        }
                    }
                }
            });
        })
        .catch(err => console.error('Error polling bracket data:', err));
    }, 10000); // Poll every 10 seconds
</script>
@endif
@endsection
