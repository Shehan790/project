<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'invite_code',
        'captain_id',
    ];

    // A team belongs to a captain (user)
    public function captain()
    {
        return $this->belongsTo(User::class, 'captain_id');
    }

    // A team has many members (users)
    public function members()
    {
        return $this->belongsToMany(User::class, 'team_user')->withTimestamps();
    }

    // A team can register for many tournaments
    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class, 'tournament_team')->withTimestamps();
    }

    // Matches where this team is team1
    public function matchesAsTeam1()
    {
        return $this->hasMany(TournamentMatch::class, 'team1_id');
    }

    // Matches where this team is team2
    public function matchesAsTeam2()
    {
        return $this->hasMany(TournamentMatch::class, 'team2_id');
    }

    // Combine matches
    public function allMatches()
    {
        return TournamentMatch::where('team1_id', $this->id)
            ->orWhere('team2_id', $this->id)
            ->get();
    }
}
