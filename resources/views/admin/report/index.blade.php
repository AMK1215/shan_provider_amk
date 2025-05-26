@extends('layouts.master')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Player Report</h1>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5>Player Summary</h5>
                        <table class="table table-bordered mb-4">
                            <thead>
                                <tr>
                                    <th>Player</th>
                                    <th>Stake Count</th>
                                    <th>Total Stake</th>
                                    <th>Total Bet</th>
                                    <th>Total Win</th>
                                    <th>Total Lose</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary as $row)
                                <tr>
                                    <td>{{ $row->member_account }}</td>
                                    <td>{{ $row->stake_count }}</td>
                                    <td>{{ $row->total_stake }}</td>
                                    <td>{{ $row->total_bet }}</td>
                                    <td>{{ $row->total_win }}</td>
                                    <td>{{ $row->total_lose }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <form method="get" class="mb-3 form-inline">
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control mx-1" placeholder="Start Date">
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control mx-1" placeholder="End Date">
                            <input type="text" name="member_account" value="{{ request('member_account') }}" class="form-control mx-1" placeholder="Member Account">
                            <select name="status" class="form-control mx-1">
                                <option value="">All Status</option>
                                <option value="BET" @if(request('status')=='BET') selected @endif>BET</option>
                                <option value="SETTLED" @if(request('status')=='SETTLED') selected @endif>SETTLED</option>
                            </select>
                            <button type="submit" class="btn btn-primary mx-1">Filter</button>
                        </form>
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Member Account</th>
                                    <th>Player ID</th>
                                    <th>Agent ID</th>
                                    <th>Provider</th>
                                    <th>Game</th>
                                    <th>Game Type</th>
                                    <th>Bet Amount</th>
                                    <th>Prize Amount</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bets as $bet)
                                <tr>
                                    <td>{{ $bet->id }}</td>
                                    <td>{{ $bet->member_account }}</td>
                                    <td>{{ $bet->player_id }}</td>
                                    <td>{{ $bet->player_agent_id }}</td>
                                    <td>{{ $bet->provider_name }}</td>
                                    <td>{{ $bet->game_name }}</td>
                                    <td>{{ $bet->game_type }}</td>
                                    <td>{{ $bet->bet_amount }}</td>
                                    <td>{{ $bet->prize_amount }}</td>
                                    <td>{{ $bet->status }}</td>
                                    <td>{{ $bet->created_at ? $bet->created_at->format('m/d/Y, h:i:s A') : '' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="11" class="text-center">No data found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div>{{ $bets->withQueryString()->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection 