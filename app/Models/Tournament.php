<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'game_title',
        'description',
        'start_date',
        'registration_deadline',
        'max_participants',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'registration_deadline' => 'datetime',
    ];

    // Teams registered in this tournament
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'tournament_team')->withTimestamps();
    }

    // Matches belonging to this tournament
    public function matches()
    {
        return $this->hasMany(TournamentMatch::class);
    }
}
