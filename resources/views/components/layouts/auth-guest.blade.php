{{--
    components/layouts/auth-guest.blade.php
    Anonymous Blade component — dipanggil sebagai <x-layouts.auth-guest title="...">
    Layout minimal untuk halaman auth (login) tanpa navbar/footer.

    Props:
      • $title — judul halaman (opsional)
    Slot:
      • $slot — konten halaman utama
--}}
@props(['title' => null])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Klasifikasi Berdaya') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="font-sans antialiased">
    {{ $slot }}
    @stack('scripts')
</body>
</html>
