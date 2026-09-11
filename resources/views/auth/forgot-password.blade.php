@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
<div style="max-width: 450px; margin: 40px auto;">
    <div class="card">
        <h2 class="card-title" style="justify-content: center; margin-bottom: 20px;">Recover Password</h2>
        
        @if (session('status'))
            <div class="alert alert-success" style="font-size: 0.85rem; padding: 10px 15px;">
                {{ session('status') }}
            </div>
        @endif

        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 25px; text-align: center;">Enter your registered email address below, and we will send you a link to reset your password.</p>

        <form action="/forgot-password" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" required placeholder="Enter your email address">
            </div>
            
            <button type="submit" class="btn btn-primary w-full mt-4" style="padding: 12px;">Request Reset Link</button>
        </form>
        
        <div class="text-center mt-4" style="margin-top: 25px;">
            <a href="/login" style="font-size: 0.9rem;">Back to Login</a>
        </div>
    </div>
</div>
@endsection
