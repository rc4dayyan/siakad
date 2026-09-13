<?php

namespace App\Services\Academic;

class AcademicScheduleBreak
{
    public const START = '16:10';

    public const END = '16:30';

    public const START_MINUTES = 970;

    public const END_MINUTES = 990;

    public const TEACHING_SLOTS = [
        ['13:00', '14:40'],
        ['14:40', '16:10'],
        ['16:30', '18:10'],
    ];

    public const PRINT_BOUNDARIES = ['13:00', '14:40', '16:10', '16:30', '18:10'];
}
