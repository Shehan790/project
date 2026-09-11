<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\Team;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Services\BracketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BracketTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test single-elimination bracket generation with 5 teams (which creates 3 byes for a power of 8).
     */
    public function test_tournament_show_page_renders_bracket_without_loop_errors()
    {
        $tournament = Tournament::create([
            'name' => 'Bracket View Test',
            'game_title' => 'Dota 2',
            'start_date' => now()->addDays(5),
            'registration_deadline' => now()->addDays(2),
            'max_participants' => 2,
            'description' => 'Render test',
            'status' => 'open',
        ]);

        $captain1 = User::create([
            'name' => 'Captain 1',
            'username' => 'captain1',
            'email' => 'captain1@domain.com',
            'password' => bcrypt('Password123!'),
            'role' => 'player',
        ]);
        $team1 = Team::create([
            'name' => 'Team One',
            'invite_code' => 'ONE123',
            'captain_id' => $captain1->id,
        ]);
        $team1->members()->attach($captain1->id);
        $tournament->teams()->attach($team1->id);

        $captain2 = User::create([
            'name' => 'Captain 2',
            'username' => 'captain2',
            'email' => 'captain2@domain.com',
            'password' => bcrypt('Password123!'),
            'role' => 'player',
        ]);
        $team2 = Team::create([
            'name' => 'Team Two',
            'invite_code' => 'TWO123',
            'captain_id' => $captain2->id,
        ]);
        $team2->members()->attach($captain2->id);
        $tournament->teams()->attach($team2->id);

        app(BracketService::class)->generateBracket($tournament);

        $response = $this->get('/tournaments/' . $tournament->id);

        $response->assertOk();
        $response->assertSee('Tournament Bracket');
    }

    public function test_bracket_generation_with_byes()
    {
        // 1. Create a tournament
        $tournament = Tournament::create([
            'name' => 'Championship Series',
            'game_title' => 'League of Legends',
            'start_date' => now()->addDays(5),
            'registration_deadline' => now()->addDays(2),
            'max_participants' => 16,
            'description' => 'Test tournament details',
            'status' => 'open',
        ]);

        // 2. Create 5 captains and 5 teams
        $teams = [];
        for ($i = 1; $i <= 5; $i++) {
            $captain = User::create([
                'name' => "Captain {$i}",
                'username' => "captain{$i}",
                'email' => "captain{$i}@domain.com",
                'password' => bcrypt('Password123!'),
                'role' => 'player',
            ]);

            $team = Team::create([
                'name' => "Team {$i}",
                'invite_code' => "CODE{$i}XX",
                'captain_id' => $captain->id,
            ]);

            // Add captain to roster
            $team->members()->attach($captain->id);
            $tournament->teams()->attach($team->id);
            $teams[] = $team;
        }

        // 3. Invoke BracketService
        $bracketService = app(BracketService::class);
        $bracketService->generateBracket($tournament);

        // 4. Verify Tournament Status changed to active
        $this->assertEquals('active', $tournament->fresh()->status);

        // 5. Verify the matches created
        // Total round 1 matches = 4 (power of 8 / 2)
        // Total round 2 matches = 2
        // Total round 3 matches = 1
        // Total = 7 matches
        $this->assertEquals(7, TournamentMatch::where('tournament_id', $tournament->id)->count());

        // 6. Verify Round 1 Byes were auto-resolved
        // A bye match has team1 or team2 as null
        $byeMatches = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('round_number', 1)
            ->where(function($q) {
                $q->whereNull('team1_id')->orWhereNull('team2_id');
            })->get();

        // 5 teams means 3 byes, so 3 matches should have a null team
        $this->assertEquals(3, $byeMatches->count());

        foreach ($byeMatches as $match) {
            $this->assertEquals('completed', $match->status);
            $this->assertNotNull($match->winner_id);
            
            // The winner of the bye should be advanced to Round 2
            $nextMatchNumber = floor($match->match_number_in_round / 2);
            $isTeam1 = ($match->match_number_in_round % 2 == 0);

            $nextMatch = TournamentMatch::where('tournament_id', $tournament->id)
                ->where('round_number', 2)
                ->where('match_number_in_round', $nextMatchNumber)
                ->first();

            $this->assertNotNull($nextMatch);
            if ($isTeam1) {
                $this->assertEquals($match->winner_id, $nextMatch->team1_id);
            } else {
                $this->assertEquals($match->winner_id, $nextMatch->team2_id);
            }
        }
    }
}
