<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans bg-white text-gray-800 antialiased custom-background">
    <style>
        .custom-background::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-image: url('{{ asset('images/background.svg') }}');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.52;
            z-index: -10;
            pointer-events: none;
            mix-blend-mode: ;
        }
    </style>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-32 sm:pt-0">
        <div>
            <a href="/">
                <x-application-logo class="fill-current text-gray-500" />
            </a>
        </div>

        <div class="w-full sm:max-w-md mt-12 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>
    </div>
</body>

</html>
