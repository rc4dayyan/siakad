@extends('base.base-auth-index')

@section('content')
    @include('base.auth.auth-signin', [
        'portalLabel' => 'Portal Mahasiswa',
        'icon' => 'fas fa-user-graduate',
        'formAction' => route('mahasiswa.auth-signin-post'),
        'forgotRoute' => route('mahasiswa.auth-forgot-page'),
        'loginLabel' => 'NIM atau email',
        'loginPlaceholder' => 'Masukkan NIM atau email',
        'periodStatusLabel' => 'Dipublikasikan',
        'periodEmptyMessage' => 'Belum ada periode yang dibuka untuk mahasiswa.',
    ])
@endsection
