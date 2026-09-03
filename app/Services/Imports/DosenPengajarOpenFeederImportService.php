<?php

namespace App\Services\Imports;

use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\MasterMataKuliah;
use App\Models\PenawaranMataKuliah;
use App\Models\ProgramStudi;
use App\Models\TahunAkademik;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DosenPengajarOpenFeederImportService
{
    private const REQUIRED_HEADERS = [
        'Semester',
        'NIDN',
        'NUPTK',
        'Nama Dosen',
        'Kode Matakuliah',
        'Nama Matakuliah',
        'Nama Kelas',
        'Tatap Muka',
        'Tatap Muka Realisasi',
        'Kode Prodi',
        'Nama Prodi',
        'Sks Ajar',
        'Jenis Evaluasi',
    ];

    public function __construct(private readonly DosenOpenFeederImportService $lecturerImporter) {}

    /**
     * @return array{lecturers_created: int, lecturers_skipped: int, offerings_created: int, offerings_updated: int}
     */
    public function import(Collection $rows, TahunAkademik $period, Kurikulum $curriculum, bool $dryRun = false): array
    {
        if ($rows->isEmpty()) {
            $this->reject('File import tidak berisi data dosen pengajar.');
        }

        $missingHeaders = array_diff(self::REQUIRED_HEADERS, array_keys($rows->first()));
        if ($missingHeaders !== []) {
            $this->reject('Kolom wajib format dosen pengajar OpenFeeder tidak ditemukan: '.implode(', ', $missingHeaders).'.');
        }

        $expectedSemester = $this->openFeederSemester($period);
        $programs = ProgramStudi::query()->get();
        $masters = MasterMataKuliah::query()->get();
        $classes = Kelas::query()->forAcademicPeriod($period)->get();
        $assignments = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $semester = $this->digits($row['Semester'] ?? null);
            $programCode = trim((string) ($row['Kode Prodi'] ?? ''));
            $courseCode = trim((string) ($row['Kode Matakuliah'] ?? ''));
            $classLabel = trim((string) ($row['Nama Kelas'] ?? ''));
            $nidn = $this->digits($row['NIDN'] ?? null);

            if ($semester !== $expectedSemester) {
                $this->reject("Baris {$rowNumber}: Semester harus {$expectedSemester} sesuai periode {$period->name}.");
            }

            $program = $programs->first(fn (ProgramStudi $item): bool => collect($item->masterMataKuliahCodes())
                ->contains(fn (string $alias): bool => strcasecmp($alias, $programCode) === 0));
            if (! $program) {
                $this->reject("Baris {$rowNumber}: program studi dengan kode {$programCode} tidak ditemukan.");
            }

            $aliases = $program->masterMataKuliahCodes();
            $master = $masters->first(fn (MasterMataKuliah $item): bool => strcasecmp((string) $item->code, $courseCode) === 0
                && collect($aliases)->contains(fn (string $alias): bool => strcasecmp($alias, (string) $item->program_studi) === 0));
            if (! $master) {
                $this->reject("Baris {$rowNumber}: Kode Matakuliah {$courseCode} belum tersedia. Jalankan import kelas pada langkah 3 terlebih dahulu.");
            }

            $className = $this->programToken($program).' '.$this->romanSemester((int) $master->semester).' '.$classLabel;
            $class = $classes->first(fn (Kelas $item): bool => (int) $item->pstudi_id === (int) $program->id
                && strcasecmp($item->name, $className) === 0);
            if (! $class) {
                $this->reject("Baris {$rowNumber}: kelas {$className} belum tersedia pada periode {$period->name}.");
            }

            if (! preg_match('/^\d{10}$/', $nidn)) {
                $this->reject("Baris {$rowNumber}: NIDN wajib terdiri dari 10 digit angka.");
            }

            $credits = filter_var($row['Sks Ajar'] ?? null, FILTER_VALIDATE_INT);
            if ($credits === false || $credits < 1 || $credits > 20) {
                $this->reject("Baris {$rowNumber}: Sks Ajar harus berupa bilangan bulat antara 1 sampai 20.");
            }

            $key = $master->id.'|'.$program->id.'|'.$class->id;
            if (isset($assignments[$key])) {
                $this->reject("Baris {$rowNumber}: mata kuliah dan kelas yang sama tercantum lebih dari sekali.");
            }

            $assignments[$key] = compact('rowNumber', 'program', 'master', 'class', 'nidn', 'credits');
        }

        DB::beginTransaction();

        try {
            $lecturerResult = $this->lecturerImporter->import($rows);
            $lecturers = Dosen::query()
                ->whereIn('dsn_nidn', collect($assignments)->pluck('nidn')->all())
                ->get()
                ->keyBy(fn ($lecturer) => (string) $lecturer->dsn_nidn);
            $offeringsCreated = 0;
            $offeringsUpdated = 0;

            foreach ($assignments as $assignment) {
                $lecturer = $lecturers->get($assignment['nidn']);
                if (! $lecturer) {
                    $this->reject("Baris {$assignment['rowNumber']}: dosen dengan NIDN {$assignment['nidn']} gagal disiapkan.");
                }

                $offering = PenawaranMataKuliah::query()->firstOrNew([
                    'master_mata_kuliah_id' => $assignment['master']->id,
                    'taka_id' => $period->id,
                    'pstudi_id' => $assignment['program']->id,
                    'kuri_id' => $curriculum->id,
                    'kelas_id' => $assignment['class']->id,
                ]);

                $offering->fill([
                    'dosen_utama_id' => $lecturer->id,
                    'code' => $offering->exists ? $offering->code : $this->offeringCode($period, $assignment),
                    'sks' => $assignment['credits'],
                    'kapasitas' => $assignment['class']->capacity ?: 40,
                    'deskripsi' => 'Penugasan dosen hasil import OpenFeeder.',
                ]);

                if ($offering->exists) {
                    if ($offering->isDirty()) {
                        $offering->save();
                        $offeringsUpdated++;
                    }
                } else {
                    if (PenawaranMataKuliah::query()->where('code', $offering->code)->exists()) {
                        $this->reject("Baris {$assignment['rowNumber']}: kode penawaran {$offering->code} sudah digunakan.");
                    }

                    $offering->save();
                    $offeringsCreated++;
                }
            }

            $dryRun ? DB::rollBack() : DB::commit();

            return [
                'lecturers_created' => $lecturerResult['imported'],
                'lecturers_skipped' => $lecturerResult['skipped'],
                'offerings_created' => $offeringsCreated,
                'offerings_updated' => $offeringsUpdated,
            ];
        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $exception;
        }
    }

    /** @param array<string, mixed> $assignment */
    private function offeringCode(TahunAkademik $period, array $assignment): string
    {
        return Str::upper(Str::slug(
            $period->code.'-'.$assignment['master']->code.'-'.$assignment['class']->code
        ));
    }

    private function openFeederSemester(TahunAkademik $period): string
    {
        $term = match ($period->term) {
            TahunAkademik::TERM_GANJIL => '1',
            TahunAkademik::TERM_GENAP => '2',
            TahunAkademik::TERM_PENDEK => '3',
            default => null,
        };

        if ($term === null) {
            $this->reject('Jenis periode akademik belum dapat dipetakan ke kode semester OpenFeeder.');
        }

        return $period->year_start.$term;
    }

    private function digits(mixed $value): string
    {
        return is_float($value) || is_int($value)
            ? number_format((float) $value, 0, '', '')
            : trim((string) ($value ?? ''));
    }

    private function programToken(ProgramStudi $program): string
    {
        $aliases = array_map('strtoupper', $program->masterMataKuliahCodes());

        return match (true) {
            in_array('86208', $aliases, true), in_array('PAI', $aliases, true) => 'PAI',
            in_array('88204', $aliases, true), in_array('PBA', $aliases, true) => 'PBA',
            in_array('86233', $aliases, true), in_array('PIAUD', $aliases, true) => 'PIAUD',
            default => Str::upper(Str::slug((string) $program->code)),
        };
    }

    private function romanSemester(int $semester): string
    {
        $roman = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII',
            8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII', 13 => 'XIII', 14 => 'XIV',
        ][$semester] ?? null;

        if ($roman === null) {
            $this->reject("Semester mata kuliah {$semester} tidak valid.");
        }

        return $roman;
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['import' => $message]);
    }
}
