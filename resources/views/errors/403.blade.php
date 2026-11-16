@extends('errors.layout')

@section('code', '403')
@section('code-color', '#f59e0b')
@section('btn-color', '#f59e0b')
@section('title', 'Akses Ditolak')
@section('message', isset($exception) && $exception->getMessage() ? $exception->getMessage() : 'Anda tidak memiliki izin untuk mengakses halaman ini.')
@section('action')
    <a href="{{ url('/') }}" class="btn">Kembali ke Beranda</a>
@endsection
