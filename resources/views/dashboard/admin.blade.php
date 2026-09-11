
@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="dashboard-header" style="display:flex; justify-content:space-between; align-items:center; gap:20px; flex-wrap:wrap;">
    <div>
        <h1 style="color: var(--text-primary);">Admin Console</h1>
        <p style="color: var(--text-muted);">Global system metrics, match dispute overrides, and audit trails.</p>
    </div>
    <div>
        <a href="#tournament-form" class="btn btn-primary" style="padding: 12px 18px;">Add Tournament</a>
    </div>
</div>

<!-- Stats Overview Grid -->
<div class="grid grid-3 mb-4">
    <div class="card text-center" style="padding: 15px;">
        <h4 style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Total Users</h4>
        <div class="numeric" style="font-size: 2.2rem; color: var(--neon-cyan); margin-top: 5px;">{{ $stats['total_users'] }}</div>
    </div>
    <div class="card text-center" style="padding: 15px;">
        <h4 style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Total Teams</h4>
        <div class="numeric" style="font-size: 2.2rem; color: var(--neon-purple); margin-top: 5px;">{{ $stats['total_teams'] }}</div>
    </div>
    <div class="card text-center" style="padding: 15px;">
        <h4 style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Tournaments Active</h4>
        <div class="numeric" style="font-size: 2.2rem; color: var(--neon-green); margin-top: 5px;">{{ $stats['total_tournaments'] }}</div>
    </div>
</div>

<div class="card mb-4">
    <h3 class="card-title" style="color: var(--neon-purple);">Tournament Registry</h3>
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
                    @foreach($tournaments as $tournament)
                        <tr>
                            <td>{{ $tournament->name }}</td>
                            <td class="numeric">{{ $tournament->teams()->count() }} / {{ $tournament->max_participants }}</td>
                            <td><span class="badge badge-{{ $tournament->status }}">{{ $tournament->status }}</span></td>
                            <td style="display:flex; gap: 8px; align-items:center;">
                                <a href="{{ route('tournaments.show', $tournament->id) }}" class="btn btn-secondary btn-small">View</a>
                                <form action="/tournaments/{{ $tournament->id }}" method="POST" onsubmit="return confirm('Delete this tournament? This cannot be undone.');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p style="color: var(--text-muted); font-size: 0.9rem; text-align:center; padding: 20px 0;">No tournaments available yet.</p>
    @endif
</div>

<div class="card mb-4" id="tournament-form">
    <h3 class="card-title" style="color: var(--neon-cyan);">Host a Tournament</h3>
    <p style="color: var(--text-muted); margin-bottom: 20px;">Admins can create tournaments directly from the admin console.</p>
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

<div class="grid grid-2">
    <!-- Audit Logs Panel -->
    <div class="card">
        <h3 class="card-title" style="color: var(--neon-cyan);">System Audit Log</h3>
        
        <div style="background-color: var(--bg-dark); padding: 15px; border-radius: 6px; font-family: monospace; font-size: 0.8rem; line-height: 1.5; max-height: 350px; overflow-y: auto;">
            @if(count($auditLogs) > 0)
                @foreach($auditLogs as $log)
                    <div style="border-bottom: 1px solid var(--border-color); padding: 8px 0; color: #a5b4fc;">
                        {{ trim($log) }}
                    </div>
                @endforeach
            @else
                <p style="color: var(--text-muted); text-align:center; padding: 20px 0;">No system logs available.</p>
            @endif
        </div>
        
        <div class="mt-4">
            <a href="/admin/users" class="btn btn-primary w-full">Manage User Roles</a>
        </div>
    </div>
    
    <!-- Dispute Resolution Center -->
    <div class="card">
        <h3 class="card-title" style="color: var(--neon-red);">Dispute Resolution</h3>
        
        @if($disputedMatches->count() > 0)
            @foreach($disputedMatches as $match)
                <div style="background-color: rgba(255, 56, 96, 0.05); border: 1px solid var(--neon-red); border-radius: 6px; padding: 15px; margin-bottom: 15px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px;">
                        <strong style="color: var(--neon-red); font-size: 0.8rem; text-transform:uppercase;">Dispute Match #{{ $match->id }}</strong>
                        <span style="color: var(--text-muted); font-size: 0.75rem;">{{ $match->tournament->name }}</span>
                    </div>
                    
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 10px;">
                        <strong>{{ $match->team1->name }}</strong> vs <strong>{{ $match->team2->name }}</strong>
                    </p>
                    
                    <div style="background-color:var(--bg-dark); padding:8px; border-radius:4px; margin-bottom: 10px; font-size:0.8rem;">
                        <span style="color: var(--text-muted); text-transform:uppercase; font-size: 0.7rem; display:block; margin-bottom:5px;">Submitted Reports:</span>
                        @foreach($match->scoreReports as $report)
                            <div>{{ $report->reporter->name }}: <span style="color:var(--neon-cyan); font-weight:bold;">{{ $report->score_team1 }} - {{ $report->score_team2 }}</span></div>
                        @endforeach
                    </div>

                    <form action="/matches/{{ $match->id }}/override" method="POST">
                        @csrf
                        <div style="display:flex; gap: 10px; align-items:center; margin-bottom: 10px;">
                            <div style="flex:1;">
                                <label class="form-label" style="font-size:0.75rem;">{{ $match->team1->name }} Score</label>
                                <input type="number" name="score_team1" class="form-control text-center" min="0" required placeholder="0">
                            </div>
                            <span style="color: var(--text-muted); font-weight:bold; margin-top:20px;">:</span>
                            <div style="flex:1;">
                                <label class="form-label" style="font-size:0.75rem;">{{ $match->team2->name }} Score</label>
                                <input type="number" name="score_team2" class="form-control text-center" min="0" required placeholder="0">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-danger btn-small w-full">Finalize Override Score</button>
                    </form>
                </div>
            @endforeach
        @else
            <p style="color: var(--text-muted); font-size: 0.9rem; text-align:center; padding: 20px 0;">No active disputes require resolution.</p>
        @endif
    </div>
</div>
@endsection
