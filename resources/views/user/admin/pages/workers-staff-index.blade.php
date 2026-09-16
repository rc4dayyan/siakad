@extends('base.base-dash-index')

@section('title', 'Data Pengguna Staff - Siakad By Internal Developer')
@section('menu', 'Data Pengguna Staff')
@section('submenu', 'Daftar Staff')
@section('urlmenu', '#')
@section('subdesc', 'Kelola akun, unit kerja, status, dan informasi kontak staff.')

@section('custom-css')
    @include('user.admin.pages.partials.worker-list-styles')
@endsection

@section('content')
    @include('user.admin.pages.partials.worker-list', [
        'pageTitle' => 'Daftar Staff',
        'pageDescription' => 'Cari dan kelola seluruh akun staff berdasarkan unit kerjanya.',
        'indexRoute' => 'web-admin.workers.staff-index',
        'createRoute' => 'web-admin.workers.staff-create',
        'editRoute' => 'web-admin.workers.staff-edit',
        'destroyRoute' => 'web-admin.workers.staff-destroy',
        'showRoleFilter' => true,
        'showImportExport' => false,
    ])
@endsection
