@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Transfer Logs</h3>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form action="{{ route('admin.transfer-logs.index') }}" method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="type">Transfer Type</label>
                                    <select name="type" id="type" class="form-control">
                                        <option value="">All Types</option>
                                        <option value="credit_transfer" {{ request('type') == 'credit_transfer' ? 'selected' : '' }}>Credit Transfer</option>
                                        <option value="debit_transfer" {{ request('type') == 'debit_transfer' ? 'selected' : '' }}>Debit Transfer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_from">Date From</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_to">Date To</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Transfer Logs Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                        <thead>
        <tr>
            <th>ID</th>
            <th>From</th>
            <th>To</th>
            <th>Amount</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transferLogs as $log)
            <tr @if($transferLog && $transferLog->id == $log->id) style="background: #ffd" @endif>
                <td>
                    <a href="{{ route('admin.PlayertransferLogDetail', $log->id) }}">{{ $log->id }}</a>
                </td>
                <td>{{ $log->fromUser->user_name ?? '-' }}</td>
                <td>{{ $log->toUser->user_name ?? '-' }}</td>
                <td>{{ $log->amount }}</td>
                <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
            </tr>
        @endforeach
    </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $transferLogs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 