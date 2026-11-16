@extends('errors.layout')

@section('code', '503')
@section('code-color', '#0ea5e9')
@section('btn-color', '#0ea5e9')
@section('title', 'Sedang dalam Pemeliharaan')
@section('message')
    {{ isset($exception) && $exception->getMessage() ? $exception->getMessage() : 'Sistem sedang dalam pemeliharaan dan akan segera kembali. Terima kasih atas kesabaran Anda.' }}
@endsection
@section('action')
    {{-- Tidak ada tombol back saat maintenance — tidak ada halaman yang bisa dikunjungi --}}
@endsection
