<?php

namespace App\Services\Academic;

use App\Models\JadwalMingguan;
use App\Models\Notification;
use Illuminate\Support\Str;

class ScheduleNotificationService
{
    public function changed(JadwalMingguan $schedule, string $description): int
    {
        $students = $schedule->penawaranMataKuliah->pesertaDisetujui()->get();
        foreach ($students as $student) {
            Notification::create([
                'auth_id' => auth()->id() ?? 0,
                'send_to' => 3,
                'student_id' => $student->id,
                'name' => 'Perubahan jadwal kuliah',
                'slug' => route('mahasiswa.home-jadkul-index', absolute: false),
                'type' => 'jadwal',
                'desc' => $description,
                'code' => 'JADWAL-'.Str::upper(Str::random(20)),
            ]);
        }

        return $students->count();
    }
}
