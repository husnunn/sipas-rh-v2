<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Store and read timezone-naive datetime columns as UTC wall-clock, independent of config('app.timezone').
 */
class UtcDatetimeCast implements CastsAttributes
{
    public bool $withoutObjectCaching = true;

    private const string STORAGE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse((string) $value, 'UTC');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse($value)->timezone('UTC')->format(self::STORAGE_FORMAT);
    }
}
