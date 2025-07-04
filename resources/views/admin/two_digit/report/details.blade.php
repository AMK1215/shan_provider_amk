@extends('layouts.master')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1>2D Bet Slip Details</h1>
                <div class="text-muted small">
                    <strong>Slip No:</strong> {{ $slip->slip_no }}<br>
                    <strong>User:</strong> {{ optional($slip->user)->user_name }}<br>
                    <strong>Total Bet:</strong> {{ number_format($slip->total_bet_amount, 2) }}<br>
                    <strong>Session:</strong> {{ ucfirst($slip->session) }}<br>
                    <strong>Status:</strong>
                    <span class="badge badge-{{ $slip->status == 'pending' ? 'warning' : ($slip->status == 'won' ? 'success' : 'secondary') }}">
                        {{ ucfirst($slip->status) }}
                    </span>
                    <br>
                    <strong>Placed At:</strong> {{ $slip->created_at->format('Y-m-d H:i') }}
                </div>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ url()->previous() }}" class="btn btn-secondary mt-2">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    @if($bets->count())
                        <table class="table table-bordered table-hover table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Number</th>
                                    <th>Amount</th>
                                    <th>Player</th>
                                    <th>Win/Lose</th>
                                    <th>Bet Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bets as $i => $bet)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td><span class="font-weight-bold">{{ $bet->bet_number }}</span></td>
                                        <td>{{ number_format($bet->bet_amount, 2) }}</td>
                                        <td>{{ optional($bet->user)->user_name }}</td>
                                        <td>
                                            @if($bet->win_lose)
                                                <span class="badge badge-success">Win</span>
                                            @else
                                                <span class="badge badge-danger">Lose</span>
                                            @endif
                                        </td>
                                        <td>{{ $bet->created_at->format('Y-m-d H:i:s') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="alert alert-info text-center">No bets found for this slip.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    $('.table').DataTable();
});
</script>
@endsection
