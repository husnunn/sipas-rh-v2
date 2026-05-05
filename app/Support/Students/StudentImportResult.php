<?php

namespace App\Support\Students;

/**
 * @phpstan-type ImportError array{sheet: string, row: int, message: string}
 */
final class StudentImportResult
{
    /**
     * @param  list<ImportError>  $errors
     */
    public function __construct(
        public int $created,
        public int $skipped,
        public array $errors = [],
    ) {}

    public function summaryMessage(): string
    {
        $parts = [
            "{$this->created} siswa ditambahkan",
        ];

        if ($this->skipped > 0) {
            $parts[] = "{$this->skipped} baris dilewati";
        }

        return implode(', ', $parts).'.';
    }
}
