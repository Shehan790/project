@extends('layouts.app')

@section('title', 'Welcome to Nexus Arena')

@section('content')
<div class="welcome-hero">
    <h1 class="welcome-title">Nexus Arena</h1>
    <p class="welcome-subtitle">The ultimate automated hub for local and semi-professional Esports tournaments. Roster up, compete, and track brackets in real time.</p>
    
    <div style="display: flex; gap: 15px; justify-content: center; margin-top: 20px;">
        <a href="/tournaments" class="btn btn-primary">Browse Tournaments</a>
        @guest
            <a href="/register" class="btn btn-secondary">Create Account</a>
        @else
            <a href="/dashboard" class="btn btn-secondary">Go to Dashboard</a>
        @endguest
    </div>
</div>

<div class="grid grid-3 mt-4" style="margin-top: 50px;">
    <div class="card text-center">
        <h3 class="card-title" style="justify-content: center; color: var(--neon-cyan);">Automated Brackets</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Say goodbye to manual sheets. Our system auto-generates balanced brackets, handles complex odd-team numbers with byes, and auto-advances winners.</p>
    </div>
    
    <div class="card text-center">
        <h3 class="card-title" style="justify-content: center; color: var(--neon-purple);">Secure Score verification</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Fair play is enforced. Captains submit results, opponents confirm or dispute within 15 minutes, with full moderator overrides available to settle scores.</p>
    </div>
    
    <div class="card text-center">
        <h3 class="card-title" style="justify-content: center; color: var(--neon-green);">Live Progress</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Stay updated with real-time bracket updates and instant match notifications so you never miss your scheduled match times.</p>
    </div>
</div>
@endsection
