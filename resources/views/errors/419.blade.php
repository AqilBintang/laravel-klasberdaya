@extends('errors.layout')

@section('code', '419')
@section('code-color', '#f59e0b')
@section('btn-color', '#f59e0b')
@section('title', 'Sesi Kedaluwarsa')
@section('message', 'Sesi Anda telah berakhir. Silakan muat ulang halaman dan coba lagi.')
@section('action')
    <a href="javascript:history.back()" class="btn">Kembali</a>
@endsection
