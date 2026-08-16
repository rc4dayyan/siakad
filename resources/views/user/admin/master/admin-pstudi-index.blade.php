@extends('base.base-dash-index')
@section('title')
    Data Master Program Studi - Siakad By Internal Developer
@endsection
@section('menu')
    Data Master Program Studi
@endsection
@section('submenu')
    Daftar Data Program Studi
@endsection
@section('submenu0')
    Tambah Data Program Studi
@endsection
@section('urlmenu')
    #
@endsection
@section('subdesc')
    Halaman untuk mengelola Data Program Studi
@endsection
@section('content')
<section class="section row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">@yield('submenu')</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPstudiModal">
                    <i class="fas fa-plus me-1"></i> Tambah Program Studi
                </button>
            </div>
            <div class="card-body">
                <table class="table table-striped" id="table1">
                    <thead>
                        <th class="text-center">#</th>
                        <th class="text-center">Nama Program Studi</th>
                        <th class="text-center">Kode Program Studi</th>
                        <th class="text-center">Gelar Program Studi</th>
                        <th class="text-center">Kepala Program Studi</th>
                        <th class="text-center">Button</th>
                    </thead>
                    <tbody>
                        @foreach ($pstudi as $key => $item)
                            <tr>
                                <td data-label="Number">{{ ++$key }}</td>
                                <td data-label="Program Studi">{{ $item->name }}</td>
                                <td data-label="Kode Program Studi">{{ ($item->fakultas?->code ?? 'Belum ditentukan') . '-' . $item->code }}</td>
                                <td data-label="Gelar Program Studi">{{ $item->level }} - ( {{ $item->title }} )</td>
                                <td data-label="Kepala Program Studi">{{ $item->head?->dsn_name ?? 'Belum ditentukan' }}</td>
                                <td class="d-flex justify-content-center align-items-center">
                                    <a href="#" style="margin-right: 10px" data-bs-toggle="modal" data-bs-target="#updatePStudi{{ $item->code }}" class="btn btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    {{-- <a href="{{ route($prefix.'staffmanager-dosen-view', $item->code) }}"  style="margin-right: 10px" class="btn btn-outline-info"><i class="fa-solid fa-eye"></i></a> --}}
                                    <form id="delete-form-{{ $item->code }}"
                                        action="{{ route($prefix.'master.pstudi-destroy', $item->code) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <a type="button" class="bs-tooltip btn btn-rounded btn-outline-danger"
                                            data-bs-toggle="tooltip" data-bs-placement="top" title="Delete"
                                            data-original-title="Delete"
                                            data-url="{{ route($prefix.'master.pstudi-destroy', $item->code) }}"
                                            data-name="{{ $item->name }}"
                                            onclick="deleteData('{{ $item->code }}')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</section>

<form action="{{ route($prefix.'master.pstudi-store') }}" method="POST">
    @csrf
    <input type="hidden" name="_form" value="create-pstudi">
    <div class="modal fade" id="createPstudiModal" tabindex="-1" aria-labelledby="createPstudiModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createPstudiModalLabel">Tambah Data Program Studi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="create-pstudi-fakultas">Fakultas</label>
                        <select name="faku_id" id="create-pstudi-fakultas" class="form-select">
                            <option value="">Pilih Fakultas</option>
                            @foreach ($fakultas as $item)
                                <option value="{{ $item->id }}" @selected(old('faku_id') == $item->id)>{{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('faku_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="create-pstudi-name">Nama Program Studi</label>
                        <input type="text" class="form-control" name="name" id="create-pstudi-name" value="{{ old('name') }}" placeholder="Inputkan nama program studi...">
                        @error('name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="create-pstudi-code">Kode Program Studi (Maksimal 10 Karakter)</label>
                        <input type="text" class="form-control" name="code" id="create-pstudi-code" value="{{ old('code') }}" placeholder="Inputkan kode program studi..." maxlength="10" uppercase onkeydown="return /[a-zA-Z0-9]/i.test(event.key)">
                        @error('code')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="create-pstudi-cnim">Kode Awal NIM (5 Angka)</label>
                        <input type="text" class="form-control" name="cnim" id="create-pstudi-cnim" value="{{ old('cnim') }}" placeholder="Inputkan kode awal NIM program studi..." maxlength="5" uppercase onkeydown="return /[0-9]/i.test(event.key)">
                        @error('cnim')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="create-pstudi-title">Gelar Program Studi</label>
                        <input type="text" class="form-control" name="title" id="create-pstudi-title" value="{{ old('title') }}" placeholder="Inputkan gelar program studi..." uppercase>
                        @error('title')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="create-pstudi-level">Jenjang Program Studi</label>
                        <input type="text" class="form-control" name="level" id="create-pstudi-level" value="{{ old('level') }}" placeholder="Inputkan jenjang program studi..." uppercase>
                        @error('level')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="create-pstudi-head">Kepala Program Studi</label>
                        <select name="head_id" id="create-pstudi-head" class="form-select">
                            <option value="">Pilih Kepala Program Studi</option>
                            @foreach ($dosen as $item)
                                <option value="{{ $item->id }}" @selected(old('head_id') == $item->id)>{{ $item->dsn_name }}</option>
                            @endforeach
                        </select>
                        @error('head_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan</button>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="me-1 mb-1 d-inline-block">

    <!--Extra Large Modal -->
    @foreach ($pstudi as $item)
    <form action="{{ route($prefix.'master.pstudi-update', $item->code) }}" method="POST" enctype="multipart/form-data">
        @method('patch')
        @csrf
        <div class="modal fade text-left w-100" id="updatePStudi{{$item->code}}" tabindex="-1" role="dialog"
            aria-labelledby="myModalLabel16" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-l"
                role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel16">Edit Program Studi - {{ $item->name }}</h4>
                        <div class="">
    
                            <button type="submit" class="btn btn-outline-primary" >
                                <i class="fas fa-paper-plane"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group">
                                <label for="faku_id">Fakultas</label>
                                <select name="faku_id" id="faku_id" class="form-select">
                                    <option value="" selected>Pilih Fakultas</option>
                                    @foreach ($fakultas as $faku)
                                        <option value="{{ $faku->id }}" {{ $item->faku_id == $faku->id ? 'selected' : '' }}>{{ $faku->name }}</option>
                                    @endforeach
                                </select>
                                @error('faku_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="name">Nama Program Studi</label>
                                <input type="text" class="form-control" name="name" id="name" placeholder="Inputkan nama program studi..." value="{{ $item->name }}">
                                @error('name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="code">Kode Program Studi ( 10 Angka )</label>
                                <input type="text" class="form-control" name="code" id="code" placeholder="Inputkan kode program studi..." value="{{ $item->code }}" maxlength="10" uppercase onkeydown="return /[a-zA-Z0-9]/i.test(event.key)" >
                                @error('code')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="cnim">Kode Awal NIM ( 5 Angka )</label>
                                <input type="text" class="form-control" name="cnim" id="cnim" placeholder="Inputkan kode awal NIM program studi..." value="{{ $item->cnim }}" maxlength="5" uppercase onkeydown="return /[0-9]/i.test(event.key)" >
                                @error('cnim')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="title">Gelar Program Studi</label>
                                <input type="text" class="form-control" name="title" id="title" placeholder="Inputkan gelar program studi..." value="{{ $item->title }}" uppercase >
                                @error('title')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="level">Jenjang Program Studi</label>
                                <input type="text" class="form-control" name="level" id="level" placeholder="Inputkan jenjang program studi..." value="{{ $item->level }}" uppercase >
                                @error('level')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="head_id">Kepala Program Studi</label>
                                <select name="head_id" id="head_id" class="form-select">
                                    <option value="" selected>Pilih Kepala Program Studi</option>
                                    @foreach ($dosen as $dsn)
                                        <option value="{{ $dsn->id }}" {{ $item->head_id == $dsn->id ? 'selected' : '' }}>{{ $dsn->dsn_name }}</option>
                                    @endforeach
                                </select>
                                @error('head_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endforeach
</div>
@endsection

@section('custom-js')
    @if ($errors->any() && old('_form') === 'create-pstudi')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('createPstudiModal');

                if (modal) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        </script>
    @endif
@endsection
