@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div style="max-width: 500px; margin: 40px auto;">
    <div class="card">
        <h2 class="card-title" style="justify-content: center; margin-bottom: 20px;">Roster Up</h2>
        
        <form action="/register" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g.Shehan De Silva">
            </div>

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" name="username" id="username" class="form-control" value="{{ old('username') }}" required placeholder="e.g. Silva99">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required placeholder="e.g.shehan@gmail.com">
            </div>

            <div class="form-group">
                <label class="form-label">Are you competing or organizing?</label>
                <div style="display: flex; gap: 20px; margin-top: 10px;">
                    <label style="color: var(--text-primary); font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                        <input type="radio" name="role" value="player" checked style="accent-color: var(--neon-cyan);"> Player / Team Captain
                    </label>
                    <label style="color: var(--text-primary); font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                        <input type="radio" name="role" value="organizer" style="accent-color: var(--neon-cyan);"> Tournament Organizer
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required placeholder="Min 8 chars, mixed case, numbers, symbols">
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required placeholder="Re-enter password">
            </div>
            
            <button type="submit" class="btn btn-primary w-full mt-4" style="padding: 12px;">Create Profile</button>
        </form>
        
        <div class="text-center mt-4" style="margin-top: 25px;">
            <p style="color: var(--text-muted); font-size: 0.9rem;">Already have an account? <a href="/login">Log In</a></p>
        </div>
    </div>
</div>
@endsection
