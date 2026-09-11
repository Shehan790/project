@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div style="max-width: 450px; margin: 40px auto;">
    <div class="card">
        <h2 class="card-title" style="justify-content: center; margin-bottom: 20px;">Player Login</h2>
        
        <form action="/login" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label" for="login">Email or Username</label>
                <input type="text" name="login" id="login" class="form-control" value="{{ old('login') }}" required autofocus placeholder="Enter your email or username">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required placeholder="Enter password">
            </div>

            <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                <label style="color: var(--text-muted); font-size: 0.85rem; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="remember" style="accent-color: var(--neon-cyan);"> Remember Me
                </label>
                <a href="/forgot-password" style="font-size: 0.85rem;">Forgot Password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary w-full mt-4" style="padding: 12px;">Enter Arena</button>
        </form>
        
        <div class="text-center mt-4" style="margin-top: 25px;">
            <p style="color: var(--text-muted); font-size: 0.9rem;">First time here? <a href="/register">Register Account</a></p>
        </div>
    </div>
</div>
@endsection
