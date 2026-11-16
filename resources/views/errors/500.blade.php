@extends('errors.layout')

@section('code', '500')
@section('code-color', '#ef4444')
@section('btn-color', '#ef4444')
@section('title', 'Terjadi Kesalahan')
@section('message', 'Terjadi kesalahan pada server. Tim kami sudah diberitahu. Silakan coba lagi dalam beberapa saat.')
@section('action')
    <a href="{{ url('/') }}" class="btn">Kembali ke Beranda</a>
@endsection
