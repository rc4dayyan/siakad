<?php

namespace App\Services\Academic;

class AcademicScheduleBreak
{
    public const START = '16:10';

    public const END = '16:30';

    public const START_MINUTES = 970;

    public const END_MINUTES = 990;

    /** @return list<array{0: int, 1: int}> */
    public static function teachingWindows(int $start, int $end): array
    {
        return array_values(array_filter([
            [$start, min($end, self::START_MINUTES)],
            [max($start, self::END_MINUTES), $end],
        ], fn (array $window): bool => $window[0] < $window[1]));
    }
}
