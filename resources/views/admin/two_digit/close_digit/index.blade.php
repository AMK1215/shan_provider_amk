@extends('layouts.master')
@section('style')
<style>
    .digits-flex-container {
    display: flex;
    flex-direction: row;
    justify-content: center;
    gap: 15px;
    overflow-x: auto;
    padding-bottom: 10px;
    /* Optional: hide scrollbar for a cleaner look */
    scrollbar-width: thin;
    scrollbar-color: #ccc #f8f9fa;
}
.digits-flex-container::-webkit-scrollbar {
    height: 8px;
}
.digits-flex-container::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 4px;
}
.digit-item {
    min-width: 80px;
    /* ...other styles remain the same... */
}
.horizontal-bar {
    display: flex;
    flex-direction: row;
    align-items: center;
    border: 1px solid #fff;
    background: #222;
    width: fit-content;
    margin: 0 auto 4px auto;
    overflow-x: auto;
    max-width: 100%;
}
.digit-box {
    border: 1px solid #fff;
    color: #fff;
    background: #222;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
}
.digit-box.active {
    background: #28a745;
    color: #fff;
    border-color: #28a745;
}
.horizontal-bar-group {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 8px;
}
.choose-digit-section {
    padding: 20px 0;
}
.choose-digit-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 12px;
    color: #333;
}
.horizontal-bar-group {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 12px;
}
.horizontal-bar-modern {
    display: flex;
    flex-direction: row;
    gap: 10px;
    justify-content: center;
    margin-bottom: 2px;
}
.digit-box-modern {
    background: #23272f;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 2px solid #444;
    color: #fff;
    width: 100px;
    height: 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    font-weight: 800;
    cursor: pointer;
    transition: background 0.2s, border 0.2s, color 0.2s, box-shadow 0.2s;
    position: relative;
    user-select: none;
}
.digit-box-modern:hover {
    background: #2d323c;
    border-color: #007bff;
    color: #fff;
    box-shadow: 0 4px 16px rgba(0,123,255,0.10);
}
.digit-box-modern.active {
    background:rgb(129, 29, 142);
    border-color: #28a745;
    border-width: 3px;
    color: #fff;
}
.digit-box-modern.inactive {
    background: #222 !important;
    border-color: #222 !important;
    color: #fff;
}
.digit-label {
    font-size: 1.2rem;
    letter-spacing: 1px;
}
.toggle-indicator {
    margin-top: 4px;
    width: 30px;
    height: 15px;
    background: #444;
    border-radius: 6px;
    position: relative;
    transition: background 0.2s;
    display: flex;
    align-items: center;
}
.digit-box-modern.active .toggle-indicator {
    background: #fff;
}
.toggle-dot {
    width: 12px;
    height: 12px;
    background: #bbb;
    border-radius: 50%;
    transition: background 0.2s, transform 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.10);
}
.digit-box-modern.active .toggle-dot {
    background: #28a745;
    transform: translateX(10px);
}
.digit-item.inactive {
    background: #222 !important;
    border-color: #222 !important;
    color: #fff;
}
.digit-item.active {
    background: #28a745;
    color: #fff;
    border-color: #28a745;
}
</style>
@endsection

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-12">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Head Close Digits</li>
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
                    <div class="card justify-content-center">
                        <div class="card-header">
                            <h3 class="card-title">Head Close Digits Management</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#headCloseDigitModal">
                                    <i class="fas fa-plus text-white mr-2"></i>Add Head Close Digit
                                </button>
                            </div>
                        </div>
                        <div class="card-body justify-content-center">
                            <!-- Flex UI for Head Close Digits -->
                            <div class="head-digits-container">
                                <h5 class="mb-3">Toggle Head Close Digits Status</h5>
                                <div class="digits-flex-container">
                                    @foreach($headCloseDigits as $digit)
                                        <div class="digit-item {{ $digit->status ? 'active' : 'inactive' }}" data-id="{{ $digit->id }}">
                                            <div class="digit-number">{{ $digit->head_close_digit }}</div>
                                            <div class="digit-toggle">
                                                <label class="switch">
                                                    <input type="checkbox" 
                                                           class="status-toggle" 
                                                           data-id="{{ $digit->id }}"
                                                           {{ $digit->status ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                            <div class="digit-status">
                                                <span class="status-text {{ $digit->status ? 'text-success' : 'text-danger' }}">
                                                    {{ $digit->status ? 'ON' : 'OFF' }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Traditional Table View (Optional) -->
                            <!-- <div class="mt-4">
                                <h5>Detailed View</h5>
                                <table id="mytable" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Choose Close Digit</th>
                                            <th>Status</th>
                                            <th>Created At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($headCloseDigits as $digit)
                                            <tr>
                                                <td class="text-sm font-weight-normal">{{ $loop->iteration }}</td>
                                                <td>{{ $digit->head_close_digit }}</td>
                                                <td>
                                                    <span class="badge {{ $digit->status ? 'badge-success' : 'badge-danger' }}">
                                                        {{ $digit->status ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td>{{ $digit->created_at->format('Y-m-d H:i:s') }}</td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm edit-digit" 
                                                            data-id="{{ $digit->id }}" 
                                                            data-digit="{{ $digit->head_close_digit }}">
                                                        Edit
                                                    </button>
                                                    <form class="d-inline" action="{{ route('admin.head-close-digit.destroy', $digit->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm delete-digit">Delete</button>
                                                    </form> 
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div> -->

                            <div class="horizontal-bar">
                                @foreach($headCloseDigits as $digit)
                                    <div class="digit-box">
                                        {{ $digit->head_close_digit }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Choose Close Digit</h3>
                        </div>
                        <div class="card-body">
                            <div class="choose-digit-section">
                                <div class="choose-digit-title">Choose Close Digit</div>
                                <!-- <div class="horizontal-bar-group">
                                    @foreach($chooseCloseDigits->chunk(10) as $chunk)
                                        <div class="horizontal-bar-modern">
                                            @foreach($chunk as $digit)
                                                <div class="digit-box-modern {{ $digit->status ? 'active' : '' }}"
                                                     data-id="{{ $digit->id }}"
                                                     data-status="{{ $digit->status }}"
                                                     onclick="toggleChooseDigitStatus(this)"
                                                     title="Click to toggle status">
                                                    <span class="digit-label">{{ $digit->choose_close_digit }}</span>
                                                    <span class="toggle-indicator">
                                                        <span class="toggle-dot"></span>
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div> -->

                                <div class="horizontal-bar-group">
                        @foreach($chooseCloseDigits->chunk(10) as $chunk)
                            <div class="horizontal-bar-modern">
                                @foreach($chunk as $digit)
                                    <div class="digit-box-modern {{ $digit->status ? 'active' : 'inactive' }}"
                                        data-id="{{ $digit->id }}"
                                        data-status="{{ $digit->status }}"
                                        onclick="toggleChooseDigitStatus(this)"
                                        title="Click to toggle status">
                                        <span class="digit-label">{{ $digit->choose_close_digit }}</span>
                                        <span class="toggle-indicator">
                                            <span class="toggle-dot"></span>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- Head Close Digit Modal -->
    <div class="modal fade" id="headCloseDigitModal" tabindex="-1" role="dialog" aria-labelledby="headCloseDigitModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="headCloseDigitModalLabel">Add Head Close Digit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="head_close_digit">Head Close Digit</label>
                            <input type="number" class="form-control @error('head_close_digit') is-invalid @enderror" 
                                   id="head_close_digit" name="head_close_digit" 
                                   min="0" max="9" placeholder="Enter digit (0-9)" required>
                            @error('head_close_digit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Add Head Close Digit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<link href="{{ asset('plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
<script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<link href="{{ asset('plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}" rel="stylesheet" />
<script src="{{ asset('plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Handle status toggle
    $('.status-toggle').on('change', function() {
        const digitId = $(this).data('id');
        const isChecked = $(this).is(':checked');
        const digitItem = $(this).closest('.digit-item');
        const statusText = digitItem.find('.status-text');

        // Update UI immediately
        if (isChecked) {
            digitItem.addClass('active').removeClass('inactive');
            statusText.removeClass('text-danger').addClass('text-success').text('ON');
        } else {
            digitItem.removeClass('active').addClass('inactive');
            statusText.removeClass('text-success').addClass('text-danger').text('OFF');
        }

        // Send AJAX request to update status
        $.ajax({
            url: '{{ route("admin.head-close-digit.toggle-status") }}',
            method: 'POST',
            data: {
                id: digitId,
                status: isChecked ? 1 : 0,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Status Updated!',
                        text: 'Head close digit status has been updated successfully.',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            },
            error: function(xhr) {
                // Revert UI if request fails
                $(this).prop('checked', !isChecked);
                if (!isChecked) {
                    digitItem.addClass('active').removeClass('inactive');
                    statusText.removeClass('text-danger').addClass('text-success').text('ON');
                } else {
                    digitItem.removeClass('active').addClass('inactive');
                    statusText.removeClass('text-success').addClass('text-danger').text('OFF');
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to update status. Please try again.',
                });
            }
        });
    });

    // Handle delete confirmation
    $('.delete-digit').on('click', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Clear form when modal is closed
    $('#headCloseDigitModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });
});

// Function to show a custom message box instead of alert()
function showMessageBox(message, type = 'info') {
    const messageBox = document.createElement('div');
    messageBox.style.cssText = `
        position: fixed;
        top: 100px;
        right: 10px;
        background-color: ${type === 'success' ? '#4CAF50' : '#f44336'};
        color: white;
        padding: 15px;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.5s ease-in-out;
    `;
    messageBox.textContent = message;
    document.body.appendChild(messageBox);

    // Fade in
    setTimeout(() => messageBox.style.opacity = '1', 10);

    // Fade out and remove after 3 seconds
    setTimeout(() => {
        messageBox.style.opacity = '0';
        messageBox.addEventListener('transitionend', () => messageBox.remove());
    }, 3000);
}

function toggleChooseDigitStatus(element) {
    const digitId = element.getAttribute('data-id');
    const currentStatus = parseInt(element.getAttribute('data-status'));
    const newStatus = currentStatus === 1 ? 0 : 1;

    // Optimistically update UI
    element.setAttribute('data-status', newStatus);
    element.classList.toggle('active', newStatus === 1);
    element.classList.toggle('inactive', newStatus === 0);

    // Optionally show a loading spinner or overlay here
    // For example: element.style.pointerEvents = 'none'; // Disable clicks during fetch

    // Send AJAX request to update status in DB
    fetch('{{ route('admin.choose-close-digit.toggle-status') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            id: digitId,
            status: newStatus
        })
    })
    .then(response => {
        if (!response.ok) { // Check if response status is not 2xx
            // If the response is not OK, try to read the error message
            return response.json().then(errorData => {
                throw new Error(errorData.message || 'Server error occurred.');
            });
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            // Revert UI if failed
            element.setAttribute('data-status', currentStatus);
            element.classList.toggle('active', currentStatus === 1);
            element.classList.toggle('inactive', currentStatus === 0);
            showMessageBox(data.message || 'Failed to update status!', 'error');
        } else {
            showMessageBox(data.message || 'စိတ်ကြိုက် ပိတ်ဂဏန်းပိတ်သိမ်းမှု့အောင်မြင်ပါသည် | Status updated successfully!', 'success');
        }
        // Hide loading spinner/re-enable clicks
        // element.style.pointerEvents = 'auto';
    })
    .catch(error => {
        // Revert UI if network error or other exception
        element.setAttribute('data-status', currentStatus);
        element.classList.toggle('active', currentStatus === 1);
        element.classList.toggle('inactive', currentStatus === 0);
        showMessageBox('Error: ' + error.message, 'error');
        // Hide loading spinner/re-enable clicks
        // element.style.pointerEvents = 'auto';
    });
}
</script>

@if (session()->has('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 1500
        });
    </script>
@endif
@endsection
