@extends('errors.layout')

@section('code', '404')
@section('code-color', '#6366f1')
@section('btn-color', '#6366f1')
@section('title', 'Halaman Tidak Ditemukan')
@section('message', 'Halaman yang Anda cari tidak ada atau sudah dipindahkan.')
@section('action')
    <a href="{{ url('/') }}" class="btn">Kembali ke Beranda</a>
@endsection
