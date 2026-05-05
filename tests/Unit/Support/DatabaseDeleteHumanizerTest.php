<?php

namespace Tests\Unit\Support;

use App\Support\DatabaseDeleteHumanizer;
use Illuminate\Database\QueryException;
use PDOException;
use Tests\TestCase;

class DatabaseDeleteHumanizerTest extends TestCase
{
    public function test_maps_mysql_style_foreign_key_violation_referencing_classes(): void
    {
        $pdoException = new PDOException(
            'SQLSTATE[23000]: Integrity constraint violation: 1451 Cannot delete or update a parent row: a foreign key constraint fails (`db`.`schedules`, CONSTRAINT `schedules_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`))',
            1451,
        );

        $exception = new QueryException('mysql', 'delete from `classes` where `id` = ?', [], $pdoException);

        $flash = DatabaseDeleteHumanizer::flash($exception, 'fallback');

        $this->assertSame('error', $flash['type']);
        $this->assertStringContainsString('Kelas', $flash['message']);
    }

    public function test_non_constraint_exception_uses_fallback_and_is_not_marked_as_integrity(): void
    {
        $exception = new \RuntimeException('something else');

        $flash = DatabaseDeleteHumanizer::flash($exception, 'Pesan fallback uji.');

        $this->assertSame('error', $flash['type']);
        $this->assertSame('Pesan fallback uji.', $flash['message']);
    }
}
