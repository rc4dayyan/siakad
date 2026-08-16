<?php

namespace Database\Seeders;

class DemoDuaTahunLaluAkademikSeeder extends DemoSatuTahunLaluAkademikSeeder
{
    protected function entryYear(): int
    {
        return 2023;
    }

    protected function prefix(): string
    {
        return 'DUATA';
    }

    protected function historyLabel(): string
    {
        return 'Dua Tahun Lalu';
    }
}
