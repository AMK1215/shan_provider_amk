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
                                        <option value="top_up" {{ request('type') == 'top_up' ? 'selected' : '' }}>TopUp</option>
                                        <option value="withdraw" {{ request('type') == 'withdraw' ? 'selected' : '' }}>Withdraw</option>
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
                    <table id="mytable" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>ApprovedBy</th>
                                    <!-- <th>Action</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transferLogs as $log)
                                <tr>
                                    <td>{{ $log->fromUser->name ?? 'N/A' }}</td>
                                    <td>{{ $log->toUser->name ?? 'N/A' }}</td>
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
                                        <span class="badge {{ $log->type === 'top_up' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst(str_replace('_', ' ', $log->type)) }}
                                        </span>
                                    </td>
                                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td>{{ $log->description }}</td>
                                    <td>{{ $log->approved_by ?? 'N/A' }}</td>
                                    <!-- <td>
                                        <a href="{{ route('admin.PlayertransferLogDetail', $log->id) }}" class="btn btn-primary">View</a>
                                    </td> -->
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No transfer logs found</td>
                                </tr>
                                @endforelse
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