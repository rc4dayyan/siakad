<?php

namespace Tests\Unit;

use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Academic\AdminKrsManagementService;
use App\Services\Academic\KrsBulkImportService;
use App\Services\Academic\KrsService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class KrsBulkImportServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_three_hundred_action_rows_pass_the_import_row_limit(): void
    {
        try {
            $this->service()->prepare($this->rows(300), new TahunAkademik, new User);
            $this->fail('Validasi baris seharusnya gagal karena NIM kosong.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Baris 2: NIM, nama, dan aksi wajib diisi.',
                $exception->errors()['import'][0]
            );
        }
    }

    public function test_more_than_three_hundred_action_rows_are_rejected(): void
    {
        try {
            $this->service()->prepare($this->rows(301), new TahunAkademik, new User);
            $this->fail('File dengan 301 baris beraksi seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Maksimal 300 baris dengan aksi dapat diproses dalam satu file.',
                $exception->errors()['import'][0]
            );
        }
    }

    private function service(): KrsBulkImportService
    {
        return new KrsBulkImportService(
            Mockery::mock(AdminKrsManagementService::class),
            Mockery::mock(KrsService::class)
        );
    }

    private function rows(int $count): Collection
    {
        return collect(array_fill(0, $count, [
            'NIM' => '',
            'Nama' => 'Mahasiswa Uji',
            'Aksi' => 'setujui',
        ]));
    }
}
