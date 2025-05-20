<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PoneWine| Log in</title>

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

</body>

</html>
