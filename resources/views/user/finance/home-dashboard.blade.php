<div class="row">
    <div class="col-lg-3 col-6 mb-2">
        <a href="{{ route($prefix . 'finance.keuangan-index') }}">
            <div class="card btn btn-outline-success">
                <div class="card-body d-flex justify-content-around align-items-center p-1">
                    <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-wallet" style="font-size: 32px"></i></span>
                    <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ number_format($balSekarang, 0, ',', '.') }}<br> Sisa Saldo <br>( IDR )</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-6 mb-2">
        <a href="{{ route($prefix . 'finance.keuangan-index') }}">
            <div class="card btn btn-outline-success">
                <div class="card-body d-flex justify-content-around align-items-center p-1">
                    <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-file-invoice-dollar" style="font-size: 32px"></i></span>
                    <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ number_format($balPending, 0, ',', '.') }}<br> Pending <br>( IDR )</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-6 mb-2">
        <a href="{{ route($prefix . 'finance.keuangan-index') }}">
            <div class="card btn btn-outline-success">
                <div class="card-body d-flex justify-content-around align-items-center p-1">
                    <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-dollar" style="font-size: 32px"></i></span>
                    <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ number_format($balIncome, 0, ',', '.') }}<br> Income <br>( IDR )</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-6 mb-2">
        <a href="{{ route($prefix . 'finance.keuangan-index') }}">
            <div class="card btn btn-outline-success">
                <div class="card-body d-flex justify-content-around align-items-center p-1">
                    <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-dollar" style="font-size: 32px"></i></span>
                    <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ number_format($balExpense, 0, ',', '.') }}<br> Expenses <br>( IDR )</span>
                </div>
            </div>
        </a>
    </div>
</div>
<div class="row">
    <div class="col-lg-3 col-6 mb-2">
        <a href="{{ route($prefix . 'finance.tagihan-index') }}">
            <div class="card btn btn-outline-success">
                <div class="card-body d-flex justify-content-around align-items-center p-1">
                    <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-file-invoice" style="font-size: 32px"></i></span>
                    <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\TagihanKuliah::all()->count() }}<br> Tagihan</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-6 mb-2">
        <a href="{{ route($prefix . 'finance.pembayaran-index') }}">
            <div class="card btn btn-outline-success">
                <div class="card-body d-flex justify-content-around align-items-center p-1">
                    <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-file-invoice-dollar" style="font-size: 32px"></i></span>
                    <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\HistoryTagihan::where('stat', 1)->count() }}<br> Pembayaran</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-6 mb-2">
        <a href="{{ route($prefix . 'finance.pembayaran-index') }}">
            <div class="card btn btn-outline-success">
                <div class="card-body d-flex justify-content-around align-items-center p-1">
                    <span class="icon" style="margin-right: 5px;"><i class="fa-solid fa-person-circle-question" style="font-size: 32px"></i></span>
                    <span class="text-putih" style="margin-left: 10px; font-size: 14px;">{{ \App\Models\uAttendance::where('absen_approve', 1)->count() }}<br> Approval</span>
                </div>
            </div>
        </a>
    </div>
</div>
