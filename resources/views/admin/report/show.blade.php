@extends('layouts.master')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Player Bet History - {{ $member_account }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('report.index') }}">Back to Summary</a></li>
                </ol>
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
                        <form method="get" class="mb-3 form-inline">
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control mx-1" placeholder="Start Date">
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control mx-1" placeholder="End Date">
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
                                    <td>{{ $bet->player_id }}</td>
                                    <td>{{ $bet->player_agent_id }}</td>
                                    <td>{{ $bet->provider_name }}</td>
                                    <td>{{ $bet->game_name }}</td>
                                    <td>{{ $bet->game_type }}</td>
                                    <td>{{ number_format($bet->bet_amount, 2) }}</td>
                                    <td>{{ number_format($bet->prize_amount, 2) }}</td>
                                    <td>{{ $bet->status }}</td>
                                    <td>{{ $bet->created_at ? $bet->created_at->format('m/d/Y, h:i:s A') : '' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="10" class="text-center">No data found.</td></tr>
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