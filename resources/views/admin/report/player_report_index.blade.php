@extends('layouts.master')

@section('style')
<style>
.digital-clock {
    font-family: 'Courier New', Courier, monospace;
    min-width: 160px;
    text-align: center;
    background: #222;
    border: 2px solid #007bff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>
@endsection


@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
           
            <div class="row mb-3">
    <div class="col-12">
        <div id="digitalClock" class="digital-clock bg-dark text-white rounded px-3 py-2 d-inline-block shadow-sm" style="font-size:1.5rem; letter-spacing:2px;"></div>
    </div>
</div>
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
                    <h5 class="mb-0">Player Report Summary Table</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                    <table id="mytable" class="table table-bordered table-hover">

                    <thead>
            <tr>
                <th>Player ID</th>
                <th>Agent ID</th>
                <th>Total Spins</th>
                <th>Total Bet</th>
                <th>Total Payout</th>
                <th>Win/Lose</th>
            </tr>
        </thead>
        <tbody>
        @foreach($report as $row)
            <tr>
                <td>{{ $row->player_user_name }}</td>
                <td>{{ $row->agent_user_name }}</td>
                <td>{{ $row->total_spins }}</td>
                <td>{{ number_format($row->total_bet, 2) }}</td>
                <td>{{ number_format($row->total_payout, 2) }}</td>
                <td>
                    @if($row->win_lose >= 0)
                        <span style="color:green">+{{ number_format($row->win_lose, 2) }}</span>
                    @else
                        <span style="color:red">{{ number_format($row->win_lose, 2) }}</span>
                    @endif
                </td>
            </tr>
        @endforeach
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
                <h4>Totals</h4>
            <p>Total Bet Amount: <strong>{{ number_format($totals['total_bet'], 2) }}</strong></p>
            <p>Total Payout Amount: <strong>{{ number_format($totals['total_payout'], 2) }}</strong></p>
            <p>Total Win/Lose:
                @if($totals['win_lose'] >= 0)
                    <strong style="color:green">+{{ number_format($totals['win_lose'], 2) }}</strong>
                @else
                    <strong style="color:red">{{ number_format($totals['win_lose'], 2) }}</strong>
                @endif
            </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 

@section('script')
<script>
function updateClock() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    const s = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('digitalClock').textContent = `${h}:${m}:${s}`;
}
setInterval(updateClock, 1000);
updateClock();
</script>
@endsection