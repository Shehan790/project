@extends('layouts.app')

@section('title', 'Organizer Dashboard')

@section('content')
<div class="dashboard-header">
    <div>
        <h1 style="color: var(--text-primary);">Organizer Dashboard</h1>
        <p style="color: var(--text-muted);">Manage tournaments, resolve disputes, and configure brackets.</p>
    </div>
</div>

<div class="grid grid-2">
    
    <!-- Left Column: Create Tournament and Active Listings -->
    <div>
        <!-- Create Tournament Form -->
        <div class="card">
            <h3 class="card-title" style="color: var(--neon-cyan); margin-bottom: 20px;">Host a Tournament</h3>
            
            <form action="/tournaments" method="POST">
                @csrf
                
                <div class="form-group">
                    <label class="form-label" for="name">Tournament Name</label>
                    <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Valorant Winter Cup">
                </div>

                <div class="form-group">
                    <label class="form-label" for="game_title">Game Title</label>
                    <input type="text" name="game_title" id="game_title" class="form-control" required placeholder="e.g. Valorant">
                </div>

                <div class="form-group">
                    <label class="form-label" for="max_participants">Max Participant Teams</label>
                    <input type="number" name="max_participants" id="max_participants" class="form-control" min="2" max="128" required placeholder="16">
                </div>

                <div class="form-group">
                    <label class="form-label" for="registration_deadline">Registration Deadline</label>
                    <input type="datetime-local" name="registration_deadline" id="registration_deadline" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="start_date">Start Date & Time</label>
                    <input type="datetime-local" name="start_date" id="start_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Rules & Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4" placeholder="Enter tournament details..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary w-full mt-4">Open Tournament Registration</button>
            </form>
        </div>
    </div>
    
    <!-- Right Column: Dispute Resolution & Tournaments Overview -->
    <div>
        
        <!-- Dispute Resolution Panel -->
        <div class="card" style="margin-bottom: 25px;">
            <h3 class="card-title" style="color: var(--neon-red);">Dispute Center</h3>
            
            @if($disputedMatches->count() > 0)
                @foreach($disputedMatches as $match)
                    <div style="background-color: rgba(255, 56, 96, 0.05); border: 1px solid var(--neon-red); border-radius: 6px; padding: 15px; margin-bottom: 15px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px;">
                            <strong style="color: var(--neon-red); font-size: 0.8rem; text-transform:uppercase;">Disputed Match #{{ $match->id }}</strong>
                            <span style="color: var(--text-muted); font-size: 0.75rem;">{{ $match->tournament->name }}</span>
                        </div>
                        
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px;">
                            Matchup: <strong>{{ $match->team1->name }}</strong> vs <strong>{{ $match->team2->name }}</strong>
                        </p>
                        
                        <!-- Show submitted reports for admin to review -->
                        <div style="background-color:var(--bg-dark); padding:10px; border-radius:4px; margin-bottom: 15px;">
                            <h4 style="font-size: 0.75rem; color: var(--text-muted); text-transform:uppercase; margin-bottom: 5px;">Submitted Reports:</h4>
                            @foreach($match->scoreReports as $report)
                                <div style="font-size: 0.85rem; margin-bottom: 5px; color: var(--text-primary);">
                                    Reported by {{ $report->reporter->name }}: <strong style="color: var(--neon-cyan);">{{ $report->score_team1 }} - {{ $report->score_team2 }}</strong>
                                </div>
                            @endforeach
                        </div>

                        <!-- Override Form -->
                        <form action="/matches/{{ $match->id }}/override" method="POST">
                            @csrf
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 10px; text-transform:uppercase;">Admin Override Decision</p>
                            <div style="display:flex; gap: 10px; align-items:center; margin-bottom: 10px;">
                                <div style="flex:1;">
                                    <label class="form-label" style="font-size: 0.7rem;">{{ $match->team1->name }} Score</label>
                                    <input type="number" name="score_team1" class="form-control text-center" min="0" required placeholder="0">
                                </div>
                                <span style="color: var(--text-muted); font-weight:bold; margin-top:20px;">:</span>
                                <div style="flex:1;">
                                    <label class="form-label" style="font-size: 0.7rem;">{{ $match->team2->name }} Score</label>
                                    <input type="number" name="score_team2" class="form-control text-center" min="0" required placeholder="0">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger btn-small w-full">Finalize Override Score</button>
                        </form>
                    </div>
                @endforeach
            @else
                <p style="color: var(--text-muted); font-size: 0.9rem; text-align:center; padding: 20px 0;">No active match disputes reported.</p>
            @endif
        </div>

        <!-- Tournaments Overview -->
        <div class="card">
            <h3 class="card-title" style="color: var(--neon-purple);">Tournaments Registry</h3>
            
            @if($tournaments->count() > 0)
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Teams</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tournaments as $t)
                                <tr>
                                    <td>{{ $t->name }}</td>
                                    <td class="numeric">{{ $t->teams()->count() }} / {{ $t->max_participants }}</td>
                                    <td>
                                        <span class="badge badge-{{ $t->status }}">{{ $t->status }}</span>
                                    </td>
                                    <td>
                                        @if($t->status === 'open' || $t->status === 'pending')
                                            @if($t->teams()->count() >= 2)
                                                <form action="/tournaments/{{ $t->id }}/start" method="POST" onsubmit="return confirm('Start tournament? This generates the matches and brackets.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary btn-small">Start</button>
                                                </form>
                                            @else
                                                <span style="font-size:0.75rem; color:var(--text-muted);">Waiting...</span>
                                            @endif
                                        @else
                                            <a href="{{ route('tournaments.show', $t->id) }}" class="btn btn-secondary btn-small">View</a>
                                            <a href="/tournaments/{{ $t->id }}/export" class="btn btn-secondary btn-small" style="margin-left:5px;">CSV</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p style="color: var(--text-muted); font-size: 0.9rem; text-align:center; padding: 20px 0;">No tournaments hosted yet.</p>
            @endif
        </div>
    </div>
</div>
@endsection
