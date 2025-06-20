@extends('layouts.master')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-12">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Deposit Request Lists</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-end mb-3">
                        <a href="{{ route('admin.agent.deposit') }}" class="btn btn-primary " style="width: 100px;"><i
                                class="fas fa-arrow-left text-white  mr-2"></i>Back</a>
                    </div>
                    <div class="card " style="border-radius: 20px;">
                        <div class="card-header">
                            <h3>Deposit Request Lists</h3>
                        </div>

                        <div class="card-body">
                            <form action="{{ route('admin.agent.deposit') }}" method="GET">
                                <div class="row mt-3">

                                        <div class="col-md-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" name="start_date"
                                                value="{{ request()->start_date }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date" class="form-control" name="end_date"
                                                value="{{ request()->end_date }}">
                                        </div>

                                    <div class="col-md-3">
                                            <label for="exampleFormControlSelect1" class="ms-0">Select Status</label>
                                            <select class="form-control" id="" name="status">
                                                <option value="all"
                                                    {{ request()->get('status') == 'all' ? 'selected' : '' }}>All
                                                </option>
                                                <option value="0"
                                                    {{ request()->get('status') == '0' ? 'selected' : '' }}>Pending
                                                </option>
                                                <option value="1"
                                                    {{ request()->get('status') == '1' ? 'selected' : '' }}>Approved
                                                </option>
                                                <option value="2"
                                                    {{ request()->get('status') == '2' ? 'selected' : '' }}>Rejected
                                                </option>
                                            </select>
                                    </div>
                                    <div class="col-md-3 mt-4 pt-2">
                                        <button class="btn btn-sm btn-primary" id="search" type="submit">Search</button>
                                        <a href="{{ route('admin.agent.deposit') }}"
                                            class="btn btn-link text-primary ms-auto border-0" >
                                            <i class="fas fa-refresh">refresh</i>
                                        </a>
                                    </div>
                                </div>
                            </form>
                            <table id="mytable" class="table table-bordered table-hover">
                                <thead>
                                    <th>#</th>
                                    <th>PlayerId</th>
                                    <th>PlayerName</th>
                                    <th>Requested Amount</th>
                                    <th>RefrenceNo</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>DateTime</th>
                                    <th>Action</th>
                                    <th>DepositLog</th>
                                </thead>
                                <tbody>
                                    @foreach ($deposits as $deposit)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $deposit->user->user_name }}</td>
                                            <td>{{ $deposit->user->name }}</td>
                                            <td>{{ number_format($deposit->amount) }}</td>
                                            <td>{{ $deposit->refrence_no }}</td>
                                            <td>{{ $deposit->bank->paymentType->name }}</td>
                                            <td>
                                                @if ($deposit->status == 0)
                                                    <span class="badge text-bg-warning text-warning mb-2">Pending</span>
                                                @elseif ($deposit->status == 1)
                                                    <span class="badge text-bg-success text-success mb-2">Approved</span>
                                                @elseif ($deposit->status == 2)
                                                    <span class="badge text-bg-danger text-danger mb-2">Rejected</span>
                                                @endif
                                            </td>
                                            <td>{{ $deposit->created_at->setTimezone('Asia/Yangon')->format('d-m-Y H:i:s') }}
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <form
                                                        action="{{ route('admin.agent.depositStatusUpdate', $deposit->id) }}"
                                                        method="post">
                                                        @csrf
                                                        <input type="hidden" name="amount"
                                                            value="{{ $deposit->amount }}">
                                                        <input type="hidden" name="status" value="1">
                                                        <input type="hidden" name="player"
                                                            value="{{ $deposit->user->id }}">
                                                        @if ($deposit->status == 0)
                                                            <button class="btn btn-success p-1 me-1" type="submit" title="Approve">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        @endif
                                                    </form>
                                                    <form
                                                        action="{{ route('admin.agent.depositStatusreject', $deposit->id) }}"
                                                        method="post">
                                                        @csrf
                                                        <input type="hidden" name="status" value="2">
                                                        @if ($deposit->status == 0)
                                                            <button class="btn btn-danger p-1 me-1" type="submit" title="Reject">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        @endif
                                                    </form>
                                                </div>
                                                <div class="d-flex align-items-center mt-1">
                                                    <a href="{{ route('admin.agent.depositView', $deposit->id) }}"
                                                        class="text-white btn btn-info btn-sm">Detail</a>
                                                </div>
                                                
                                            </td>
                                            <td>
                                            <div class="d-flex align-items-center">
                                                    <a href="{{ route('admin.agent.depositLog', $deposit->id) }}"
                                                        class="text-white btn btn-info btn-sm">Log</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <th></th>
                                    <th></th>
                                    <th>Total Amounts</th>
                                    <th>{{number_format($totalDeposits)}}</th>
                                    <th>Total Deposit Count</th>
                                    <th>{{$deposits->count()}}</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>


                                </tfoot>

                            </table>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
            </div>
        </div>
    </section>
@endsection
