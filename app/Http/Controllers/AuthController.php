<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'], // Username or email
            'password' => ['required', 'string'],
        ]);

        // Attempt login using either email or username
        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $credentials['login'])->first();

        // Failed attempts check
        if ($user) {
            // Check lockout: if locked out (simulated via key in cache or session, or simple check)
            $lockoutKey = 'login_lockout_' . $user->id;
            $failedAttemptsKey = 'login_failed_' . $user->id;
            
            if (session()->get($lockoutKey) && session()->get($lockoutKey) > now()) {
                $seconds = session()->get($lockoutKey)->diffInSeconds(now());
                return back()->withErrors([
                    'login' => "Too many failed attempts. Account locked. Try again in " . ceil($seconds / 60) . " minutes."
                ]);
            }
        }

        if (Auth::attempt([$field => $credentials['login'], 'password' => $credentials['password']], $request->filled('remember'))) {
            $request->session()->regenerate();
            
            // Reset failed login attempts on success
            if ($user) {
                session()->forget('login_failed_' . $user->id);
            }

            // Log authentication event
            Log::info("User logged in: " . Auth::user()->username);

            return redirect()->intended('/dashboard');
        }

        // Increment failed attempts
        if ($user) {
            $failedAttempts = session()->get('login_failed_' . $user->id, 0) + 1;
            session()->put('login_failed_' . $user->id, $failedAttempts);

            Log::warning("Failed login attempt for user: " . $user->username . " (Attempt {$failedAttempts}/3)");

            if ($failedAttempts >= 3) {
                session()->put('login_lockout_' . $user->id, now()->addMinutes(15));
                session()->forget('login_failed_' . $user->id);
                return back()->withErrors([
                    'login' => 'Too many failed attempts. Account locked for 15 minutes.'
                ]);
            }
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->onlyInput('login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'role' => ['required', 'in:organizer,player'], // Player or Organizer registration
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        Log::info("New user registered: " . $user->username . " with role: " . $user->role);

        Auth::login($user);

        return redirect('/dashboard')->with('success', 'Registration successful. Welcome!');
    }

    public function logout(Request $request)
    {
        $username = Auth::user()->username ?? 'Unknown';
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info("User logged out: " . $username);

        return redirect('/')->with('success', 'Logged out successfully.');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        // Log the reset email (simulated SMTP)
        Log::info("Password reset link requested for email: " . $request->email);

        return back()->with('status', 'A password reset link has been sent to your email address (simulated in logs).');
    }
}
