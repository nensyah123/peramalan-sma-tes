<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Forecasting Nensyah</title>
    <link rel="icon" href="{{ asset('template/img/logorental.png') }}">

```
<!-- SB Admin -->
<link href="{{ asset('template/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
<link href="{{ asset('template/css/sb-admin-2.min.css') }}" rel="stylesheet">
```

<style>

:root{
    --primary-red:#74271f;
}

/* ================= BACKGROUND ================= */

body{
    height:100vh;
    margin:0;
    display:flex;
    align-items:center;
    justify-content:center;

    background-image:url("{{ asset('template/img/mobil.png') }}");
    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;
}

/* Overlay gelap halus */
body::before{
    content:"";
    position:absolute;
    inset:0;
    background:rgba(0,0,0,0.30);
}

/* ================= LOGIN ================= */

.login-wrapper{
    position:relative;
    z-index:2;
    width:420px;
}

/* Shadow abu premium */
.login-card{
    border-radius:22px;
    padding:45px;
    background:#ffffff;

    box-shadow:
        0 20px 60px rgba(120,120,120,0.35),
        0 8px 20px rgba(150,150,150,0.25);

    transition:.3s ease;
}

.login-card:hover{
    transform:translateY(-4px);
}

/* ===== LOGO TAJAM ===== */

.login-logo{
    height:95px;
    margin-bottom:25px;

    image-rendering:-webkit-optimize-contrast;
    image-rendering:crisp-edges;

    filter:contrast(1.1) saturate(1.05);
}

/* Input */
.form-control{
    border-radius:14px;
    height:48px;
    border:1px solid #ddd;
    padding-left:15px;
}

.form-control:focus{
    border-color:var(--primary-red);
    box-shadow:0 0 0 3px rgba(116,39,31,0.15);
}

/* Tombol */
.btn-primary{
    background:var(--primary-red);
    border:none;
    border-radius:14px;
    height:48px;
    font-weight:700;
    letter-spacing:.5px;

    box-shadow:0 6px 18px rgba(150,150,150,.35);
    transition:.3s;
}

.btn-primary:hover{
    background:#a03023;
    transform:translateY(-2px);
}

/* Responsive */
@media(max-width:500px){

    .login-wrapper{
        width:90%;
    }

    .login-card{
        padding:35px 25px;
    }

}

</style>

</head>

<body>

<div class="login-wrapper">

```
<div class="login-card text-center">

    <img src="{{ asset('template/img/logorental.png') }}"
         class="login-logo"
         alt="Logo Rental">

    <form action="{{ route('login.authenticate') }}" method="POST">
        @csrf

        <div class="form-group text-left">
            <input type="email"
                   name="email"
                   class="form-control"
                   placeholder="Masukkan Email"
                   value="{{ old('email') }}"
                   required autofocus>

            @error('email')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group text-left">
            <input type="password"
                   name="password"
                   class="form-control"
                   placeholder="Masukkan Password"
                   required>
        </div>

        <button class="btn btn-primary btn-block">
            LOGIN
        </button>

    </form>

</div>
```

</div>

<script src="{{ asset('template/vendor/jquery/jquery.min.js') }}"></script>

<script src="{{ asset('template/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

</body>
</html>
