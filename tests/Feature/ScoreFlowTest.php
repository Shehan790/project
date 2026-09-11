<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\Team;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Models\ScoreReport;
use App\Models\Notification;
use App\Services\BracketService;
use App\Services\ScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ScoreFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $bracketService;
    protected $scoreService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bracketService = app(BracketService::class);
        $this->scoreService = app(ScoreService::class);
    }

    public function test_tournament_page_shows_score_report_form_for_captains()
    {
        $tournament = Tournament::create([
            'name' => 'Nexus Cup',
            'game_title' => 'Valorant',
            'start_date' => now()->addDays(2),
            'registration_deadline' => now()->addDay(),
            'max_participants' => 2,
            'description' => 'Finals test',
            'status' => 'open',
        ]);

        $cap1 = User::create([
            'name' => 'Captain 1', 'username' => 'cap1', 'email' => 'cap1@domain.com', 'password' => bcrypt('Password123!'), 'role' => 'player'
        ]);
        $team1 = Team::create(['name' => 'Alpha Team', 'invite_code' => 'ALPHA123', 'captain_id' => $cap1->id]);
        $team1->members()->attach($cap1->id);
        $tournament->teams()->attach($team1->id);

        $cap2 = User::create([
            'name' => 'Captain 2', 'username' => 'cap2', 'email' => 'cap2@domain.com', 'password' => bcrypt('Password123!'), 'role' => 'player'
        ]);
        $team2 = Team::create(['name' => 'Beta Team', 'invite_code' => 'BETA1234', 'captain_id' => $cap2->id]);
        $team2->members()->attach($cap2->id);
        $tournament->teams()->attach($team2->id);

        $this->bracketService->generateBracket($tournament);
        $match = TournamentMatch::where('tournament_id', $tournament->id)->first();

        $this->actingAs($cap1);
        $response = $this->get("/tournaments/{$tournament->id}");

        $response->assertStatus(200);
        $response->assertSee('Report Score');
        $response->assertSee("/matches/{$match->id}/report");
    }

    /**
     * Test a complete score submission and verification flow.
     */
    public function test_score_submission_and_confirmation_flow()
    {
        Queue::fake();

        // 1. Setup tournament and 2 teams
        $tournament = Tournament::create([
            'name' => 'Nexus Cup',
            'game_title' => 'Valorant',
            'start_date' => now()->addDays(2),
            'registration_deadline' => now()->addDay(),
            'max_participants' => 2,
            'description' => 'Finals test',
            'status' => 'open',
        ]);

        $cap1 = User::create([
            'name' => 'Captain 1', 'username' => 'cap1', 'email' => 'cap1@domain.com', 'password' => bcrypt('Password123!'), 'role' => 'player'
        ]);
        $team1 = Team::create(['name' => 'Alpha Team', 'invite_code' => 'ALPHA123', 'captain_id' => $cap1->id]);
        $team1->members()->attach($cap1->id);
        $tournament->teams()->attach($team1->id);

        $cap2 = User::create([
            'name' => 'Captain 2', 'username' => 'cap2', 'email' => 'cap2@domain.com', 'password' => bcrypt('Password123!'), 'role' => 'player'
        ]);
        $team2 = Team::create(['name' => 'Beta Team', 'invite_code' => 'BETA1234', 'captain_id' => $cap2->id]);
        $team2->members()->attach($cap2->id);
        $tournament->teams()->attach($team2->id);

        // 2. Start tournament
        $this->bracketService->generateBracket($tournament);

        $match = TournamentMatch::where('tournament_id', $tournament->id)->first();
        $this->assertNotNull($match);
        $this->assertEquals('scheduled', $match->status);

        // 3. Captain 1 reports score: Alpha Team 13 - 10 Beta Team
        $report = $this->scoreService->submitReport($match, $cap1->id, 13, 10);

        // Assert pending report
        $this->assertEquals('pending', $report->status);
        $this->assertEquals(13, $report->score_team1);
        $this->assertEquals(10, $report->score_team2);

        // Assert opponent captain received notification
        $this->assertEquals(1, Notification::where('user_id', $cap2->id)->count());

        // 4. Captain 2 confirms score
        $this->scoreService->confirmReport($report, $cap2->id);

        // Assert finalized states
        $this->assertEquals('confirmed', $report->fresh()->status);
        $this->assertEquals('completed', $match->fresh()->status);
        $this->assertEquals($team1->id, $match->fresh()->winner_id);

        // Assert tournament is completed (since it was the final round!)
        $this->assertEquals('completed', $tournament->fresh()->status);
    }

    /**
     * Test a dispute raised by the opponent captain.
     */
    public function test_score_dispute_flow()
    {
        Queue::fake();

        $tournament = Tournament::create([
            'name' => 'Nexus Cup', 'game_title' => 'Valorant', 'start_date' => now()->addDays(2), 'registration_deadline' => now()->addDay(), 'max_participants' => 2, 'description' => 'Finals test', 'status' => 'open',
        ]);

        $cap1 = User::create([
            'name' => 'Captain 1', 'username' => 'cap1', 'email' => 'cap1@domain.com', 'password' => bcrypt('Password123!'), 'role' => 'player'
        ]);
        $team1 = Team::create(['name' => 'Alpha Team', 'invite_code' => 'ALPHA123', 'captain_id' => $cap1->id]);
        $team1->members()->attach($cap1->id);
        $tournament->teams()->attach($team1->id);

        $cap2 = User::create([
            'name' => 'Captain 2', 'username' => 'cap2', 'email' => 'cap2@domain.com', 'password' => bcrypt('Password123!'), 'role' => 'player'
        ]);
        $team2 = Team::create(['name' => 'Beta Team', 'invite_code' => 'BETA1234', 'captain_id' => $cap2->id]);
        $team2->members()->attach($cap2->id);
        $tournament->teams()->attach($team2->id);

        $this->bracketService->generateBracket($tournament);
        $match = TournamentMatch::where('tournament_id', $tournament->id)->first();

        // Admin/Organizer account for notification assertions
        $org = User::create([
            'name' => 'Organizer', 'username' => 'org', 'email' => 'org@domain.com', 'password' => bcrypt('Password123!'), 'role' => 'organizer'
        ]);

        // Captain 1 reports
        $report = $this->scoreService->submitReport($match, $cap1->id, 13, 10);

        // Captain 2 disputes
        $this->scoreService->disputeReport($report, $cap2->id);

        // Assert disputed states
        $this->assertEquals('disputed', $report->fresh()->status);
        $this->assertEquals('disputed', $match->fresh()->status);
        
        // Assert organizer was notified
        $this->assertTrue(Notification::where('user_id', $org->id)->exists());

        // 5. Organizer overrides score: Beta Team wins 13 - 11
        $this->scoreService->overrideScore($match, 11, 13, $org->id);

        // Assert finalized states after override
        $this->assertEquals('completed', $match->fresh()->status);
        $this->assertEquals($team2->id, $match->fresh()->winner_id);
    }
}
