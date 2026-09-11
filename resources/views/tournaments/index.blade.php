@extends('layouts.app')

@section('title', 'Browse Tournaments')

@section('content')
<div class="dashboard-header">
    <div>
        <h1 style="color: var(--text-primary);">Tournaments Lobby</h1>
        <p style="color: var(--text-muted);">Explore open registrations, live brackets, and completed standings.</p>
    </div>
</div>

<div class="grid grid-3">
    @if($tournaments->count() > 0)
        @foreach($tournaments as $t)
            <div class="card">
                <div class="card-title" style="border-bottom:none; margin-bottom:5px; padding-bottom:0;">
                    <span style="color: var(--neon-cyan);">{{ $t->name }}</span>
                </div>
                <div style="font-size:0.8rem; font-family:'Orbitron'; color:var(--neon-purple); margin-bottom: 15px; text-transform:uppercase;">
                    {{ $t->game_title }}
                </div>
                
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 15px; height: 60px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                    {{ $t->description ?: 'No details provided.' }}
                </p>
                
                <div style="border-top: 1px solid var(--border-color); padding-top: 15px; margin-bottom: 20px; font-size: 0.8rem; color: var(--text-muted);">
                    <div style="display:flex; justify-content:space-between; margin-bottom: 5px;">
                        <span>Slots Filled:</span>
                        <strong class="numeric" style="color: var(--text-primary);">{{ $t->teams()->count() }} / {{ $t->max_participants }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom: 5px;">
                        <span>Reg Deadline:</span>
                        <strong style="color: var(--text-primary);">{{ $t->registration_deadline->format('M d, H:i') }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span>Start Date:</span>
                        <strong style="color: var(--text-primary);">{{ $t->start_date->format('M d, H:i') }}</strong>
                    </div>
                </div>
                
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span class="badge badge-{{ $t->status }}">{{ $t->status }}</span>
                    <a href="{{ route('tournaments.show', $t->id) }}" class="btn btn-primary btn-small">View Arena</a>
                </div>
            </div>
        @endforeach
    @else
        <div class="card text-center" style="grid-column: 1 / -1; padding: 40px 0;">
            <p style="color: var(--text-muted);">No tournaments have been registered in the arena yet.</p>
        </div>
    @endif
</div>
@endsection
