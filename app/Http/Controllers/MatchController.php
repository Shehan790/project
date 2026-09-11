<?php

namespace App\Http\Controllers;

use App\Models\TournamentMatch;
use App\Models\ScoreReport;
use App\Services\ScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class MatchController extends Controller
{
    protected $scoreService;

    public function __construct(ScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    /**
     * Submit score report.
     */
    public function report(Request $request, TournamentMatch $match)
    {
        $request->validate([
            'score_team1' => 'required|integer|min:0',
            'score_team2' => 'required|integer|min:0',
        ]);

        try {
            $this->scoreService->submitReport(
                $match,
                Auth::id(),
                $request->score_team1,
                $request->score_team2
            );

            return back()->with('success', 'Match score reported successfully! Waiting for opponent confirmation.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Confirm score report.
     */
    public function confirm(Request $request, ScoreReport $scoreReport)
    {
        try {
            $this->scoreService->confirmReport($scoreReport, Auth::id());
            return back()->with('success', 'Match score confirmed and finalized!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Dispute score report.
     */
    public function dispute(Request $request, ScoreReport $scoreReport)
    {
        try {
            $this->scoreService->disputeReport($scoreReport, Auth::id());
            return back()->with('success', 'Match score disputed. Organizers have been notified.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
