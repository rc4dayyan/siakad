@extends('base.base-dash-index')
@section('title', 'Transkrip Nilai Sementara - SIAKAD')
@section('menu', 'Transkrip Nilai')
@section('submenu', 'Transkrip Nilai Sementara')
@section('subdesc', 'Transkrip seluruh semester dengan tampilan yang sama seperti dokumen cetak')
@section('custom-css')
    @include('base.cetak.transkrip-styles')
    <style>
        .transcript-preview { overflow-x: auto; padding: 20px; background: #f1f3f5; }
        .transcript-preview .transcript-document {
            width: 1123px;
            min-width: 1123px;
            margin: 0 auto;
            padding: 34px;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .08);
        }
    </style>
@endsection
@section('content')
<section class="section">
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h5 class="card-title mb-0">Transkrip Nilai Sementara</h5>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('mahasiswa.akademik.nilai-index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Nilai Kuliah
                </a>
                <a href="{{ route('mahasiswa.akademik.nilai-transkrip-print') }}" class="btn btn-danger" target="_blank" rel="noopener">
                    <i class="fas fa-file-pdf me-1"></i> Cetak Transkrip
                </a>
            </div>
        </div>
        <div class="card-body">
            <p class="small text-muted">Tampilan sesuai dokumen cetak A4 landscape. Geser ke samping pada layar kecil untuk melihat seluruh transkrip.</p>
            <div class="transcript-preview" tabindex="0" role="region" aria-label="Pratinjau transkrip nilai">
                <div class="transcript-document">
                    @include('base.cetak.transkrip-content')
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
