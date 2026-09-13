<?php

namespace Tests\Unit;

use App\Services\Academic\GradeConversionService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class GradeConversionServiceTest extends TestCase
{
    public function test_thresholds_and_decimal_scores(): void
    {
        foreach ([
            [0, 'E', 0], [40.99, 'E', 0], [41, 'D', 1], [55.99, 'D', 1],
            [56, 'C', 2], [70.99, 'C', 2], [71, 'B', 3], [85.5, 'B', 3],
            [85.99, 'B', 3], [86, 'A', 4], [100, 'A', 4],
        ] as [$score, $letter, $index]) {
            $this->assertSame(['nilai' => $letter, 'nilai_indeks' => $index], GradeConversionService::convert($score));
        }
    }

    public function test_invalid_score_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GradeConversionService::convert(100.01);
    }

    public function test_empty_score_preserves_manual_grades(): void
    {
        $manual = [['nilai_angka' => null, 'nilai' => 'B', 'nilai_indeks' => 3]];
        $this->assertSame($manual, GradeConversionService::apply($manual));
    }
}
