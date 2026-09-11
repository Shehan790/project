<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\AdminController;

// 1. Public / Guest Routes
Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return view('welcome');
})->name('home');

Route::get('/tournaments', [TournamentController::class, 'index']);
Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');
Route::get('/tournaments/{tournament}/bracket/data', [TournamentController::class, 'getBracketData']);
Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');

// 2. Auth Guest Routes (only accessible when logged out)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword']);
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink']);
});

// 3. Authenticated Routes (require active session)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/notifications/read', [DashboardController::class, 'markNotificationsRead']);

    // Team management
    Route::post('/teams', [TeamController::class, 'store']);
    Route::post('/teams/join', [TeamController::class, 'join']);
    Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember']);

    // Tournament registrations
    Route::post('/tournaments/{tournament}/register', [TournamentController::class, 'register']);

    // Match scoring reporting, confirmations & disputes
    Route::post('/matches/{match}/report', [MatchController::class, 'report']);
    Route::post('/score-reports/{scoreReport}/confirm', [MatchController::class, 'confirm']);
    Route::post('/score-reports/{scoreReport}/dispute', [MatchController::class, 'dispute']);

    // Organizer & Admin actions
    Route::post('/tournaments', [TournamentController::class, 'store']);
    Route::post('/tournaments/{tournament}/start', [TournamentController::class, 'start']);
    Route::delete('/tournaments/{tournament}', [TournamentController::class, 'destroy']);
    Route::post('/matches/{match}/override', [AdminController::class, 'resolveDispute']);
    Route::get('/tournaments/{tournament}/export', [AdminController::class, 'exportTournamentCSV']);

    // Admin only actions
    Route::get('/admin/users', [AdminController::class, 'usersIndex']);
    Route::post('/admin/users/{targetUser}/role', [AdminController::class, 'updateUserRole']);
    Route::delete('/admin/users/{targetUser}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
});

