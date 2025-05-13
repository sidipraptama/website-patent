<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page</title>
    <!-- Mengarah ke file CSS yang benar -->
    <link href="{{ asset('react/dist/assets/index.css') }}" rel="stylesheet">
    <link href="{{ asset('react/dist/assets/index-1.css') }}" rel="stylesheet">
</head>

<body>
    <div id="root"></div> <!-- React akan di-mount di sini -->
    <!-- Mengarah ke file JS yang benar -->
    <script src="{{ asset('react/dist/assets/index.js') }}"></script>
    <script src="{{ asset('react/dist/assets/index-1.js') }}"></script>
</body>

</html>
