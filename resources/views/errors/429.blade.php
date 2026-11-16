@extends('errors.layout')

@section('code', '429')
@section('code-color', '#ef4444')
@section('btn-color', '#ef4444')
@section('title', 'Terlalu Banyak Permintaan')
@section('message', 'Anda telah mengirim terlalu banyak permintaan dalam waktu singkat. Silakan tunggu sebentar lalu coba lagi.')
@section('action')
    <a href="javascript:history.back()" class="btn">Kembali</a>
@endsection
