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
                                    <th>Player</th>
                                    <th>Stake Count</th>
                                    <th>Total Stake</th>
                                    <th>Total Bet</th>
                                    <th>Total Win</th>
                                    <th>Total Lose</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($summary as $row)
                                <tr>
                                    <td>{{ $row->member_account }}</td>
                                    <td>{{ $row->stake_count }}</td>
                                    <td>{{ number_format($row->total_stake, 2) }}</td>
                                    <td>{{ number_format($row->total_bet, 2) }}</td>
                                    <td>{{ number_format($row->total_win, 2) }}</td>
                                    <td>{{ number_format($row->total_lose, 2) }}</td>
                                    <td>
                                        <a href="{{ route('admin.report.show', ['member_account' => $row->member_account, 'start_date' => request('start_date'), 'end_date' => request('end_date'), 'status' => request('status')]) }}" 
                                           class="btn btn-sm btn-info">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center">No data found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection 