<?php

namespace App\Support;

final class MonthlyIncomeBands
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'lt_3m' => 'Di bawah Rp 3.000.000',
            'm3_to_8m' => 'Rp 3.000.000 – Rp 8.000.000',
            'm8_to_15m' => 'Rp 8.000.000 – Rp 15.000.000',
            'm15_to_30m' => 'Rp 15.000.000 – Rp 30.000.000',
            'gte_30m' => 'Di atas Rp 30.000.000',
            'unknown' => 'Tidak diketahui / tidak dipublikasikan',
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::options());
    }
}
