<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScoreReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'reporter_id',
        'score_team1',
        'score_team2',
        'status',
    ];

    // Belongs to a match
    public function match()
    {
        return $this->belongsTo(TournamentMatch::class, 'match_id');
    }

    // Belongs to the reporter (user)
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
