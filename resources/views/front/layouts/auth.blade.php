<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Sign in | {{ config('app.name', 'TRESCA Hub') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body.auth-page {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at 15% 20%, rgba(22, 163, 74, .10), transparent 28rem),
                linear-gradient(135deg, #f8fafc 0%, #edf6f1 100%);
            color: #111827;
            font-family: 'DM Sans', sans-serif;
        }
        .auth-page-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px 16px;
        }
        .auth-page-panel {
            width: 100%;
            max-width: 440px;
        }
        .auth-page-panel .card {
            box-shadow: 0 18px 45px rgba(15, 23, 42, .10) !important;
        }
        .auth-page-panel .form-control {
            min-height: 46px;
            border-color: #d1d5db;
        }
        .auth-page-panel .form-control:focus {
            border-color: #16a34a;
            box-shadow: 0 0 0 .2rem rgba(22, 163, 74, .12);
        }
        @media (max-width: 575.98px) {
            .auth-page-shell { align-items: start; padding: 24px 12px; }
            .auth-page-panel .card-body { padding: 1.75rem !important; }
        }
    </style>
</head>
<body class="auth-page">
    <main class="auth-page-shell">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
