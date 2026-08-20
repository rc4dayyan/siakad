@extends('base.base-auth-index')

@section('content')
    @include('base.auth.auth-signin', [
        'portalLabel' => 'Portal Admin / Pegawai',
        'icon' => 'fas fa-user-shield',
        'formAction' => route('admin.auth-signin-post'),
        'forgotRoute' => route('admin.auth-forgot-page'),
        'loginLabel' => 'Username, nomor telepon, atau email',
        'loginPlaceholder' => 'Masukkan username, nomor telepon, atau email',
        'periodStatusLabel' => 'Aktif internal',
        'periodEmptyMessage' => 'Belum ada periode akademik yang aktif.',
    ])
@endsection
