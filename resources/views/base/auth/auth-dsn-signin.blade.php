@extends('base.base-auth-index')

@section('content')
    @include('base.auth.auth-signin', [
        'portalLabel' => 'Portal Dosen',
        'icon' => 'fas fa-chalkboard-teacher',
        'formAction' => route('dosen.auth-signin-post'),
        'forgotRoute' => route('dosen.auth-forgot-page'),
        'loginLabel' => 'NIDN, nomor telepon, atau email',
        'loginPlaceholder' => 'Masukkan NIDN, nomor telepon, atau email',
    ])
@endsection
