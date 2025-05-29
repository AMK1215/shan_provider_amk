@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Transfer Logs</h2>
    <div class="card">
        <div class="card-body">
            <!-- Filter Form -->
            <form method="GET" class="row g-3 mb-3">
                <div class="col-md-3">
                    <input type="text" name="type" class="form-control" placeholder="Type (deposit/withdraw)" value="{{ request('type') }}">
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
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>SubAgent</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transferLogs as $log)
                            <tr>
                                <td>{{ $loop->iteration + ($transferLogs->currentPage() - 1) * $transferLogs->perPage() }}</td>
                                <td>{{ $log->sub_agent_name ?? '-' }}</td>
                                <td>{{ $log->fromUser->user_name ?? '-' }}</td>
                                <td>{{ $log->toUser->user_name ?? '-' }}</td>
                                <td>{{ number_format($log->amount, 2) }}</td>
                                <td>
                                    <span class="badge {{ $log->type == 'deposit' ? 'bg-success' : 'bg-danger' }}">
                                        {{ ucfirst($log->type) }}
                                    </span>
                                </td>
                                <td>{{ $log->description }}</td>
                                <td>{{ \Carbon\Carbon::parse($log->created_at)->timezone('Asia/Yangon')->format('d-m-Y H:i:s') }}</td>
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
</div>
@endsection 