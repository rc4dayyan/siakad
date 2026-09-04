@once
    <style>
        .portal-schedule-filter {
            margin-bottom: 22px;
            padding: 18px;
            border: 1px solid #dce9e4;
            border-radius: 13px;
            background: linear-gradient(135deg, #f6fbf9 0%, #ffffff 100%);
        }

        .portal-schedule-filter__heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 15px;
        }

        .portal-schedule-filter__title {
            margin: 0 0 3px;
            color: #263d36;
            font-size: 15px;
            font-weight: 700;
        }

        .portal-schedule-filter__description {
            margin: 0;
            color: #71837d;
            font-size: 12px;
        }

        .portal-schedule-filter .form-label {
            margin-bottom: 6px;
            color: #415a52;
            font-size: 12px;
            font-weight: 700;
        }

        .portal-schedule-filter .form-control,
        .portal-schedule-filter .form-select,
        .portal-schedule-filter .input-group-text {
            min-height: 40px;
            border-color: #d7e4df;
        }

        .portal-schedule-filter .form-control,
        .portal-schedule-filter .form-select {
            border-radius: 9px;
        }

        .portal-schedule-filter .input-group-text {
            border-radius: 9px 0 0 9px;
        }

        .portal-schedule-filter .input-group .form-control {
            border-radius: 0 9px 9px 0;
        }

        @media (max-width: 767.98px) {
            .portal-schedule-filter__heading {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
@endonce

<div class="portal-schedule-filter">
    <div class="portal-schedule-filter__heading">
        <div>
            <h6 class="portal-schedule-filter__title">
                <i class="fas fa-sliders-h text-primary me-2"></i>Filter Jadwal Kuliah
            </h6>
            <p class="portal-schedule-filter__description">
                Saring jadwal {{ $filterContext ?? 'perkuliahan' }} pada periode {{ $selectedPeriod?->name ?? 'aktif' }}.
            </p>
        </div>
        <span class="badge bg-primary rounded-pill px-3 py-2">{{ $jadkul->count() }} jadwal ditemukan</span>
    </div>

    <form method="GET" action="{{ route($filterRoute) }}" class="row g-3 align-items-end">
        <div class="col-xl-4 col-md-6">
            <label for="portal_schedule_keyword" class="form-label">Pencarian</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="search" name="q" id="portal_schedule_keyword"
                    class="form-control border-start-0 ps-0" value="{{ $filters['q'] ?? '' }}"
                    placeholder="Mata kuliah, kelas, atau kode jadwal">
            </div>
        </div>
        <div class="col-xl-2 col-md-6">
            <label for="portal_schedule_class" class="form-label">Kelas</label>
            <select name="kelas_id" id="portal_schedule_class" class="form-select">
                <option value="">Semua kelas</option>
                @foreach ($filterClasses as $class)
                    <option value="{{ $class->id }}" @selected(($filters['kelas_id'] ?? null) == $class->id)>
                        {{ $class->name }} ({{ $class->code }})
                    </option>
                @endforeach
            </select>
        </div>
        @isset($filterLecturers)
            <div class="col-xl-2 col-md-6">
                <label for="portal_schedule_lecturer" class="form-label">Dosen</label>
                <select name="dosen_id" id="portal_schedule_lecturer" class="form-select">
                    <option value="">Semua dosen</option>
                    @foreach ($filterLecturers as $lecturer)
                        <option value="{{ $lecturer->id }}" @selected(($filters['dosen_id'] ?? null) == $lecturer->id)>
                            {{ $lecturer->dsn_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endisset
        @if ($showMethodFilter ?? true)
            <div class="col-xl-2 col-md-6">
                <label for="portal_schedule_method" class="form-label">Metode</label>
                <select name="meth_id" id="portal_schedule_method" class="form-select">
                    <option value="">Semua metode</option>
                    <option value="0" @selected(isset($filters['meth_id']) && (int) $filters['meth_id'] === 0)>Tatap Muka</option>
                    <option value="1" @selected(isset($filters['meth_id']) && (int) $filters['meth_id'] === 1)>Teleconference</option>
                </select>
            </div>
        @endif
        <div class="col-xl-2 col-md-6">
            <label for="portal_schedule_day" class="form-label">Hari</label>
            <select name="days_id" id="portal_schedule_day" class="form-select">
                <option value="">Semua hari</option>
                @foreach (['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'] as $dayId => $dayName)
                    <option value="{{ $dayId }}" @selected(isset($filters['days_id']) && (int) $filters['days_id'] === $dayId)>
                        {{ $dayName }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-xl-2 col-md-6">
            <label for="portal_schedule_room" class="form-label">Ruangan</label>
            <select name="ruang_id" id="portal_schedule_room" class="form-select">
                <option value="">Semua ruangan</option>
                @foreach ($filterRooms as $room)
                    <option value="{{ $room->id }}" @selected(($filters['ruang_id'] ?? null) == $room->id)>
                        {{ $room->name }}
                    </option>
                @endforeach
            </select>
        </div>
        @if ($showDateFilters ?? true)
            <div class="col-xl-2 col-md-6">
                <label for="portal_schedule_date_from" class="form-label">Tanggal Mulai</label>
                <input type="date" name="date_from" id="portal_schedule_date_from" class="form-control"
                    value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-xl-2 col-md-6">
                <label for="portal_schedule_date_to" class="form-label">Tanggal Akhir</label>
                <input type="date" name="date_to" id="portal_schedule_date_to" class="form-control"
                    value="{{ $filters['date_to'] ?? '' }}">
            </div>
        @endif
        <div class="col-xl-2 col-md-6">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fas fa-filter me-1"></i> Terapkan
                </button>
                @if (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
                    <a href="{{ route($filterRoute) }}" class="btn btn-outline-secondary"
                        title="Reset filter" aria-label="Reset filter">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>
</div>
