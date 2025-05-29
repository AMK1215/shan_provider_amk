@extends('layouts.master')

@section('content')
<section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Report Detail</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Player Reports Detail</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-end mb-3">
                        <a href="{{ route('home') }}" class="btn btn-success " style="width: 100px;"><i
                                class="fas fa-plus text-white  mr-2"></i>Back</a>
                    </div>
                    <div class="card">
                        <div class="card-body">
                       
            <table id="mytable" class="table table-bordered table-hover">

                        <thead>
            <tr>
                <th>#</th>
                <th>PlayerID</th>
                <th>Provider</th>
                <th>Game</th>
                <!-- <th>Game Type</th> -->
                <th>Bet Amount</th>
                <th>Payout</th>
                <th>Win/Lost</th>
                <th>Before Balance</th>
                <th>After Balance</th>
                <th>Request Time</th>
                <!-- <th>Status</th> -->
            </tr>
        </thead>
        <tbody>
            @foreach($bets as $index => $bet)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $bet->member_account }}</td>
                <td>{{ $bet->provider_name }}</td>
                <td>{{ $bet->game_name }}</td>
                <!-- <td>{{ $bet->game_type }}</td> -->
                <td>{{ number_format($bet->bet_amount, 2) }}</td>
                <td>{{ number_format($bet->prize_amount, 2) }}</td>
                <td>{{ number_format($bet->prize_amount - $bet->bet_amount, 2) }}</td>
                <td>{{ number_format($bet->before_balance, 2) }}</td>
                <td>{{ number_format($bet->balance, 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($bet->request_time)->timezone('Asia/Yangon')->format('d-m-Y H:i:s') }}</td>
                <!-- <td>{{ $bet->request_time }}</td> -->
                <!-- <td>{{ $bet->status }}</td> -->
            </tr>
            @endforeach
        </tbody>
                        </table>



                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                     <div class="card mb-3">
                        <div class="card-body">
                            <h5>Total Stake: {{ number_format($total_stake, 2) }}</h5>
                            <h5>Total Bet: {{ number_format($total_bet, 2) }}</h5>
                            <h5>Total Win: {{ number_format($total_win, 2) }}</h5>
                            <h5>Total Lost: {{ number_format($total_lost, 2) }}</h5>
                        </div>
                     </div>
                </div>

            </div>
        </div>
    </section>
@endsection

    @section('script')
    
@endsection
