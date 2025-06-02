@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-3">Player Report Summary</h4>
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
    <div class="row mb-3 justify-content-center">
        <div class="col-12 col-lg-11 col-xl-10">
            <div class="card shadow rounded">
                <div class="card-header bg-light border-bottom-0">
                    <h5 class="mb-0">Player Summary Table</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                    <table id="mytable" class="table table-bordered table-hover">

                            <thead class="thead-light">
                                <tr>
                                    <th>Player</th>
                                    <th>Stake Count</th>
                                    <th>Total Bet</th>
                                    <th>TotalPayoutAmount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($report as $row)
                                    <tr>
                                        <td>{{ $row->member_account }}</td>
                                        <td><span class="badge badge-info">{{ $row->stake_count }}</span></td>
                                        <td class="text-right text-success">{{ number_format($row->total_bet, 2) }}</td>
                                        <td class="text-right text-info">{{ number_format($row->total_win, 2) }}</td>
                                        <td>
                                            <a href="{{ route('admin.report.detail', ['member_account' => $row->member_account]) }}" class="btn btn-sm btn-outline-primary">View Details</a>
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
            </div>
        </div>
    </div>
    <div class="row mb-3 justify-content-center">
        <div class="col-12 col-lg-8 col-xl-6">
            <div class="card shadow rounded">
                <div class="card-body text-center">
                    <h5 class="mb-3">Totals</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Total Stake Count: <strong>{{ $total['totalstake'] }}</strong></li>
                        <li class="list-group-item">Total Bet Amount: <strong class="text-success">{{ number_format($total['totalBetAmt'], 2) }}</strong></li>
                        <li class="list-group-item">Total Payout Amount: <strong class="text-info">{{ number_format($total['totalWinAmt'], 2) }}</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 