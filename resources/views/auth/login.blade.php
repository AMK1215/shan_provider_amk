<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GSC PLUS | Dashboard</title>

    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}">
    <style>
        /* Default styles (desktop view) */
        .login-page {
            background-image: url(assets/img/logo/default-logo.png);
            background-repeat: no-repeat;
            background-size: cover;
            height: 100vh;
            /* Full viewport height */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Mobile view adjustments */
        @media (max-width: 768px) {
            .login-page {
                background-size: cover;
                background-image: url(img/mobile.png);
                padding: 20px;
                /* Add padding for smaller screens */
            }

        }
    </style>
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <h2 class="text-white">Login</h2>
        </div>
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Sign in to start your session</p>
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="input-group mb-3">
                        <input id="" type="text"
                            class="form-control @error('user_name') is-invalid @enderror" name="user_name"
                            value="{{ old('user_name') }}" required placeholder="Enter User Name" autofocus>
                        @error('user_name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input id="password" type="password"
                            class="form-control @error('password') is-invalid @enderror" name="password" required
                            placeholder="Enter Password">

                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-eye" onclick="PwdView()" id="eye"
                                    style="cursor: pointer;"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <div class="icheck-primary">
                                <input type="checkbox" id="remember">
                                <label for="remember">
                                    Remember Me
                                </label>
                            </div>
                        </div>

                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                        </div>

                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- chat box -->
        <!-- Telegram Chat Box -->
<div id="chatPopup" style="position: fixed; bottom: 20px; right: 20px; width: 320px; display: none; z-index: 1000;">
    <div style="background: #007bff; color: white; padding: 10px; border-radius: 10px 10px 0 0; cursor: pointer;" onclick="toggleChat()">💬 Need Help?</div>
    <div style="background: white; border: 1px solid #ccc; border-top: none; padding: 10px; max-height: 400px; overflow-y: auto;" id="chatBox">
        <div><small>Bot: Hello! Welcome to PoneWine. Type your message below.</small></div>
    </div>
    <form id="chatForm" onsubmit="sendChat(event)" style="display: flex; border-top: 1px solid #ccc;">
        <input type="text" id="chatInput" placeholder="Type..." required style="flex: 1; padding: 5px; border: none;">
        <button type="submit" style="padding: 5px 10px; border: none; background: #007bff; color: white;">Send</button>
    </form>
</div>

<!-- Chat Toggle Button -->
<button onclick="toggleChat()" style="position: fixed; bottom: 20px; right: 20px; z-index: 999; background: #007bff; color: white; border: none; border-radius: 50%; width: 50px; height: 50px; font-size: 18px;">
    💬
</button>

    <!-- chat box end -->


    <script>
        function PwdView() {
            var x = document.getElementById("password");
            var y = document.getElementById("eye");

            if (x.type === "password") {
                x.type = "text";
                y.classList.remove('fa-eye');
                y.classList.add('fa-eye-slash');
            } else {
                x.type = "password";
                y.classList.remove('fa-eye-slash');
                y.classList.add('fa-eye');
            }
        }
    </script>

    <!-- chat box -->
    <script>
    function toggleChat() {
        const chat = document.getElementById('chatPopup');
        chat.style.display = chat.style.display === 'none' ? 'block' : 'none';
    }

    async function sendChat(event) {
        event.preventDefault();
        const input = document.getElementById('chatInput');
        const text = input.value.trim();
        if (!text) return;

        const chatBox = document.getElementById('chatBox');
        chatBox.innerHTML += `<div><strong>You:</strong> ${text}</div>`;
        input.value = '';

        const res = await fetch('{{ route('web.telegram.send') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ message: text })
        });
        const data = await res.json();
        chatBox.innerHTML += `<div><strong>Bot:</strong> ${data.reply}</div>`;
        chatBox.scrollTop = chatBox.scrollHeight;
    }
</script>

    <!-- chat box -->

</body>

</html>
