@extends('layouts.master')
@section('style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/material-icons@1.13.12/iconfont/material-icons.min.css">
@endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-12">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active">Deposit Request Detail</li>
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
                <div class="card">
                    <div class="card-header">
                        <h4>Deposit Request Detail</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-3">
                                <img src="{{ asset('assets/img/deposit/' . $deposit->image) }}" class="img-fluid rounded" alt="">
                            </div>
                            <div class="col-lg-6">

                                <div class="custom-form-group">
                                    <label class="form-label">User Name</label>
                                    <input type="text" class="form-control" name="name"
                                        value="{{ $deposit->user->name }}" readonly>
                                </div>
                                <div class="custom-form-group">
                                    <label class="form-label">Amount</label>
                                    <input type="text" class="form-control" name="amount"
                                        value="{{ $deposit->amount }}" readonly>
                                </div>
                                <div class="custom-form-group">
                                    <label class="form-label">RefrenceNo</label>
                                    <input type="text" class="form-control" name="amount"
                                        value="{{ $deposit->refrence_no }}" readonly>
                                </div>
                                <div class="custom-form-group">
                                    <label class="form-label">DateTime</label>
                                    <input type="text" class="form-control" name="amount"
                                        value="{{ $deposit->created_at->setTimezone('Asia/Yangon')->format('d-m-Y H:i:s') }}"
                                        readonly>
                                </div>
                                <div class="custom-form-group">
                                    <label class="form-label">Bank Account Name</label>
                                    <input type="text" class="form-control" name="account_name"
                                        value="{{ $deposit->bank->account_name }}" readonly>
                                </div>
                                <div class="custom-form-group"><label class="form-label">Bank Account No</label>
                                    <input type="text" class="form-control" name="account_no"
                                        value="{{ $deposit->bank->account_number }}" readonly>
                                </div>
                                <div class="custom-form-group">
                                    <label class="form-label">Payment Method</label>
                                    <input type="text" class="form-control" name=""
                                        value="{{ $deposit->bank->paymentType->name }}" readonly>
                                </div>
                                <div class="d-lg-flex mt-5">
                                    <form action="{{ route('admin.agent.depositStatusreject', $deposit->id) }}"
                                        method="post">
                                        @csrf
                                        <input type="hidden" name="status" value="2">
                                        @if ($deposit->status == 0)
                                        <button class="btn btn-danger" type="submit">
                                            Reject
                                        </button>
                                        @endif
                                    </form>
                                    <form action="{{ route('admin.agent.depositStatusUpdate', $deposit->id) }}"
                                        method="post">
                                        @csrf
                                        <input type="hidden" name="amount" value="{{ $deposit->amount }}">
                                        <input type="hidden" name="status" value="1">
                                        <input type="hidden" name="player" value="{{ $deposit->user_id }}">
                                        @if ($deposit->status == 0)
                                        <button class="btn btn-success" type="submit" style="margin-left: 5px">
                                            Approve
                                        </button>
                                        @endif
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('script')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var errorMessage = @json(session('error'));
        var successMessage = @json(session('success'));
        console.log(successMessage);
        @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: successMessage,
            background: 'hsl(230, 40%, 10%)',
            timer: 3000,
            showConfirmButton: false
        });
        @elseif(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errorMessage,
            background: 'hsl(230, 40%, 10%)',
            timer: 3000,
            showConfirmButton: false
        });
        @endif
    });
</script>
@endsection