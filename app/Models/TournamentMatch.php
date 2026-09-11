<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TournamentMatch extends Model
{
    use HasFactory;

    // Explicitly define matches table to bypass default model pluralization naming conventions
    protected $table = 'matches';

    protected $fillable = [
        'tournament_id',
        'team1_id',
        'team2_id',
        'winner_id',
        'round_number',
        'match_number_in_round',
        'match_time',
        'status',
    ];

    protected $casts = [
        'match_time' => 'datetime',
    ];

    // Belongs to a tournament
    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    // Belongs to team 1
    public function team1()
    {
        return $this->belongsTo(Team::class, 'team1_id');
    }

    // Belongs to team 2
    public function team2()
    {
        return $this->belongsTo(Team::class, 'team2_id');
    }

    // Belongs to winner team
    public function winner()
    {
        return $this->belongsTo(Team::class, 'winner_id');
    }

    // Has many score reports (often two, one from each team)
    public function scoreReports()
    {
        return $this->hasMany(ScoreReport::class, 'match_id');
    }
}
