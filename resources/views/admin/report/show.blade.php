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
                    <li class="breadcrumb-item"><a href="{{ route('admin.report.index') }}">Back to Summary</a></li>
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
                        <div>{{ $bets->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection 