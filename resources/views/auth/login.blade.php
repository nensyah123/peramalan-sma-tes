<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - MSJ Trans</title>
    <link rel="icon" href="{{ asset('template/img/logorental.png') }}">

    <!-- SB Admin -->
    <link href="{{ asset('template/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('template/css/sb-admin-2.min.css') }}" rel="stylesheet">

    <style>
        :root {
            --primary-red: #74271f;
            --primary-red-hover: #a03023;
            --shadow-color: rgba(100, 100, 100, 0.3);
        }

        /* ================= RESET & BODY ================= */
        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url("{{ asset('template/img/mobil.png') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Overlay gelap halus */
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
            z-index: 1;
        }

        /* ================= LOGIN WRAPPER ================= */
        .login-wrapper {
            position: relative;
            z-index: 2;
            width: 400px;
            max-width: 95vw;
            animation: fadeInUp 0.5s ease forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ================= CARD ================= */
        .login-card {
            border-radius: 20px;
            padding: 45px 40px 40px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow:
                0 25px 60px rgba(0, 0, 0, 0.4),
                0 8px 25px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 30px 70px rgba(0, 0, 0, 0.45),
                0 12px 30px rgba(0, 0, 0, 0.25);
        }

        /* ================= LOGO ================= */
        .login-logo {
            height: 70px;
            margin-bottom: 28px;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
            filter: contrast(1.1) saturate(1.05);
        }

        /* ================= FORM ================= */
        .form-group {
            margin-bottom: 16px;
        }

        .form-control {
            border-radius: 12px;
            height: 48px;
            border: 1.5px solid #e0e0e0;
            padding-left: 16px;
            font-size: 14px;
            color: #333;
            background: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control::placeholder {
            color: #aaa;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(116, 39, 31, 0.15);
        }

        /* ================= TOMBOL ================= */
        .btn-login {
            display: block;
            width: 100%;
            height: 48px;
            margin-top: 24px;
            background: var(--primary-red);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 1px;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(116, 39, 31, 0.4);
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        }

        .btn-login:hover {
            background: var(--primary-red-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(116, 39, 31, 0.5);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* ================= ERROR ================= */
        .alert-danger {
            border-radius: 10px;
            font-size: 13px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }

        .text-danger {
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }

        /* ================= RESPONSIVE ================= */
        @media (max-width: 500px) {
            .login-card {
                padding: 35px 25px 30px;
            }
        }
    </style>
</head>

<body>

<div class="login-wrapper">
    <div class="login-card text-center">

        <img src="{{ asset('template/img/logorental.png') }}"
             class="login-logo"
             alt="Logo Rental">

        {{-- Pesan error umum --}}
        @if(session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('login.authenticate') }}" method="POST">
            @csrf

            <div class="form-group text-left">
                <input type="email"
                       name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="Masukkan Email"
                       value="{{ old('email') }}"
                       required
                       autofocus>
                @error('email')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group text-left">
                <input type="password"
                       name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="Masukkan Password"
                       required>
                @error('password')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <button type="submit" class="btn-login">
                LOGIN
            </button>

        </form>

    </div>
</div>

<script src="{{ asset('template/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('template/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

</body>
</html>
