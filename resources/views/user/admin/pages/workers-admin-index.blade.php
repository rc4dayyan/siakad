@extends('base.base-dash-index')

@section('title', 'Data Web Administrator - Siakad By Internal Developer')
@section('menu', 'Data Pengguna Admin')
@section('submenu', 'Daftar Web Administrator')
@section('urlmenu', '#')
@section('subdesc', 'Kelola akun dan akses Web Administrator dalam satu halaman.')

@section('custom-css')
    @include('user.admin.pages.partials.worker-list-styles')
@endsection

@section('content')
    @include('user.admin.pages.partials.worker-list', [
        'pageTitle' => 'Daftar Web Administrator',
        'pageDescription' => 'Cari dan kelola akun dengan akses penuh ke sistem.',
        'indexRoute' => 'web-admin.workers.admin-index',
        'createRoute' => 'web-admin.workers.admin-create',
        'editRoute' => 'web-admin.workers.admin-edit',
        'destroyRoute' => 'web-admin.workers.admin-destroy',
        'showRoleFilter' => false,
        'showImportExport' => true,
    ])
@endsection

@section('custom-js')
@if ($errors->has('import'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('importUsers')).show();
});
</script>
@endif
@endsection
