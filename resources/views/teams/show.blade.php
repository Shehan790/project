@extends('layouts.app')

@section('title', 'Team Profile - ' . $team->name)

@section('content')
<div class="dashboard-header">
    <div>
        <h1 style="color: var(--text-primary); font-family:'Orbitron';">{{ $team->name }}</h1>
        <p style="color: var(--text-muted);">Captain: <span style="color: var(--neon-cyan); font-weight:bold;">{{ $team->captain->name }}</span></p>
    </div>
</div>

<div class="grid grid-2" style="grid-template-columns: 1fr 2fr; gap: 30px;">
    <!-- Roster Panel -->
    <div class="card">
        <h3 class="card-title" style="color: var(--neon-cyan);">Roster</h3>
        
        <ul style="list-style:none; padding:0;">
            @foreach($team->members as $member)
                <li style="padding: 12px 0; border-bottom: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <span style="font-weight:600; color:var(--text-primary);">{{ $member->name }}</span>
                        <span style="font-size:0.75rem; color:var(--text-muted); display:block;">@ {{$member->username}}</span>
                    </div>
                    @if($member->id === $team->captain_id)
                        <span class="badge badge-pending">Captain</span>
                    @else
                        <span class="badge badge-open" style="border-color:var(--border-color); color:var(--text-muted);">Member</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
    
    <!-- Match Records / History Panel -->
    <div class="card">
        <h3 class="card-title" style="color: var(--neon-purple);">Match Record</h3>
        
        @if($matches->count() > 0)
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Tournament</th>
                            <th>Round</th>
                            <th>Opponent</th>
                            <th>Score</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($matches as $match)
                            @php
                                $isTeam1 = ($match->team1_id === $team->id);
                                $opponent = $isTeam1 ? $match->team2 : $match->team1;
                                $scoreReport = $match->scoreReports()->where('status', 'confirmed')->first();
                                
                                $result = '-';
                                if ($match->status === 'completed') {
                                    $result = ($match->winner_id === $team->id) ? 'WIN' : 'LOSS';
                                } elseif ($match->status === 'disputed') {
                                    $result = 'DISPUTE';
                                }
                            @endphp
                            <tr>
                                <td>{{ $match->tournament->name }}</td>
                                <td>Round {{ $match->round_number }}</td>
                                <td>
                                    @if($opponent)
                                        <a href="{{ route('teams.show', $opponent->id) }}" style="font-weight:600;">{{ $opponent->name }}</a>
                                    @else
                                        Bye/Pending
                                    @endif
                                </td>
                                <td class="numeric">
                                    @if($scoreReport)
                                        {{ $isTeam1 ? $scoreReport->score_team1 : $scoreReport->score_team2 }} - {{ $isTeam1 ? $scoreReport->score_team2 : $scoreReport->score_team1 }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($result === 'WIN')
                                        <span class="badge badge-completed">{{ $result }}</span>
                                    @elseif($result === 'LOSS')
                                        <span class="badge badge-disputed" style="background-color:rgba(255,56,96,0.15); color:var(--neon-red); border-color:var(--neon-red);">{{ $result }}</span>
                                    @elseif($result === 'DISPUTE')
                                        <span class="badge badge-disputed">{{ $result }}</span>
                                    @else
                                        <span class="badge badge-open">{{ $match->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p style="color: var(--text-muted); font-size: 0.95rem; text-align:center; padding: 50px 0;">This team has not played any matches yet.</p>
        @endif
    </div>
</div>
@endsection
