@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4>Player Report Summary</h4>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-12">
            <form method="GET" action="">
                <div class="form-row align-items-end">
                    <div class="col-auto">
                        <label for="start_date">Start Date</label>
                        <input type="date" class="form-control" name="start_date" id="start_date" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-auto">
                        <label for="end_date">End Date</label>
                        <input type="date" class="form-control" name="end_date" id="end_date" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-auto">
                        <label for="member_account">Player</label>
                        <input type="text" class="form-control" name="member_account" id="member_account" value="{{ request('member_account') }}" placeholder="Player Username">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-12">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Player</th>
                        <th>Stake Count</th>
                        <th>Total Bet</th>
                        <th>Total Win</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report as $row)
                        <tr>
                            <td>{{ $row->member_account }}</td>
                            <td>{{ $row->stake_count }}</td>
                            <td>{{ number_format($row->total_bet, 2) }}</td>
                            <td>{{ number_format($row->total_win, 2) }}</td>
                            <td>
                                <a href="{{ route('admin.report.detail', ['member_account' => $row->member_account]) }}" class="btn btn-sm btn-info">View Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No data found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5>Totals</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Total Stake Count: <strong>{{ $total['totalstake'] }}</strong></li>
                        <li class="list-group-item">Total Bet Amount: <strong>{{ number_format($total['totalBetAmt'], 2) }}</strong></li>
                        <li class="list-group-item">Total Win Amount: <strong>{{ number_format($total['totalWinAmt'], 2) }}</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 