@extends('layouts.master')

@section('content')
<section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Transfer Log</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Transfer Log</li>
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
            <!-- Filter Form -->
            <form method="GET" class="row g-3 mb-3">
                <!-- <div class="col-md-3">
                    <input type="text" name="type" class="form-control" placeholder="Type (deposit/withdraw)" value="{{ request('type') }}">
                </div> -->
                <div class="col-md-3">
                <label for="type">Transfer Type</label>
                <select name="type" id="type" class="form-control">
                    <option value="">ChooseTypes</option>
                    <option value="top_up" {{ request('type') == 'top_up' ? 'selected' : '' }}>TopUp</option>
                    <option value="withdraw" {{ request('type') == 'withdraw' ? 'selected' : '' }}>Withdraw</option>
                </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
            <!-- Table -->
            <div class="table-responsive">
                <table id="mytable" class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>ApprovedBy</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transferLogs as $log)
                            <tr>
                                <td>{{ $loop->iteration + ($transferLogs->currentPage() - 1) * $transferLogs->perPage() }}</td>
                                <td>{{ $log->fromUser->user_name ?? '-' }}</td>
                                <td>{{ $log->toUser->user_name ?? '-' }}</td>
                                <td>
                                @if($log->type === 'top_up')
                                    <span class="badge badge-success">
                                        + {{ number_format($log->amount, 2) }}
                                    </span>
                                @else
                                    <span class="badge badge-danger">
                                    - {{ number_format($log->amount, 2) }}
                                    </span>
                                @endif
                                </td>
                                <td>
                                    <span class="badge {{ $log->type == 'top_up' ? 'bg-success' : 'bg-danger' }}">
                                        {{ ucfirst($log->type) }}
                                    </span>
                                </td>
                                <td>{{ $log->description }}</td>
                                <td>{{ \Carbon\Carbon::parse($log->created_at)->timezone('Asia/Yangon')->format('d-m-Y H:i:s') }}</td>
                                <td>{{ $log->sub_agent_name ?? '-' }}</td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No transfer logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div>
                {{ $transferLogs->withQueryString()->links() }}
            </div>
        </div>
    </div>
                    <!-- /.card -->
                </div>

            </div>
        </div>
</section>


@endsection 