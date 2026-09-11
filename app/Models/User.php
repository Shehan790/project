<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Role helper methods
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOrganizer(): bool
    {
        return $this->role === 'organizer';
    }

    public function isPlayer(): bool
    {
        return $this->role === 'player';
    }

    // A user can captain one team
    public function captainedTeam()
    {
        return $this->hasOne(Team::class, 'captain_id');
    }

    // A user can belong to multiple teams
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_user')->withTimestamps();
    }

    // A user can receive many notifications
    public function notifications()
    {
        return $this->hasMany(Notification::class)->latest();
    }
}
