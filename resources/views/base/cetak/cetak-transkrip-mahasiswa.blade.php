<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Transkrip Nilai Sementara {{ $student->mhs_nim }}</title>
    <style>@page { size: A4 landscape; margin: 9mm; } body { margin: 0; }</style>
    @include('base.cetak.transkrip-styles')
</head>
<body class="transcript-document">
    @include('base.cetak.transkrip-content')
</body>
</html>
