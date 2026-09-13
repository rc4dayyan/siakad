<?php

namespace App\Services\Academic;

use InvalidArgumentException;

class GradeConversionService
{
    // SPMI STAI PUI Majalengka, halaman 31. Desimal menggunakan batas bawah kategori.
    public const SCALE = [
        ['minimum' => 86, 'huruf' => 'A', 'indeks' => 4],
        ['minimum' => 71, 'huruf' => 'B', 'indeks' => 3],
        ['minimum' => 56, 'huruf' => 'C', 'indeks' => 2],
        ['minimum' => 41, 'huruf' => 'D', 'indeks' => 1],
        ['minimum' => 0, 'huruf' => 'E', 'indeks' => 0],
    ];

    public static function convert(float $score): array
    {
        if (! is_finite($score) || $score < 0 || $score > 100) {
            throw new InvalidArgumentException('Nilai angka harus antara 0 dan 100.');
        }

        foreach (self::SCALE as $category) {
            if ($score >= $category['minimum']) {
                return ['nilai' => $category['huruf'], 'nilai_indeks' => $category['indeks']];
            }
        }

        throw new InvalidArgumentException('Nilai angka tidak dapat dikonversi.');
    }

    public static function apply(array $grades): array
    {
        return array_map(function (array $grade): array {
            $score = $grade['nilai_angka'] ?? null;

            return $score !== null && $score !== ''
                ? array_replace($grade, self::convert((float) $score))
                : $grade;
        }, $grades);
    }
}
