<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\SchoolYear;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create admin user
        User::factory()->admin()->create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@robithotulhikmah.sch.id',
        ]);

        // 2. Create active school year
        $schoolYear = SchoolYear::factory()->active()->create([
            'name' => '2026/2027',
            'start_date' => '2026-07-15',
            'end_date' => '2027-06-30',
        ]);

        // 3. Create subjects
        $subjects = collect([
            ['code' => 'MTK', 'name' => 'Matematika'],
            ['code' => 'BIN', 'name' => 'Bahasa Indonesia'],
            ['code' => 'BIG', 'name' => 'Bahasa Inggris'],
            ['code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam'],
            ['code' => 'IPS', 'name' => 'Ilmu Pengetahuan Sosial'],
            ['code' => 'FIQ', 'name' => 'Fiqih'],
            ['code' => 'AQD', 'name' => 'Aqidah Akhlak'],
            ['code' => 'QHD', 'name' => 'Quran Hadist'],
            ['code' => 'SKI', 'name' => 'Sejarah Kebudayaan Islam'],
            ['code' => 'BAR', 'name' => 'Bahasa Arab'],
        ])->map(fn ($data) => Subject::create($data));

        // 4. Create teachers with profiles
        $teachers = TeacherProfile::factory(5)
            ->recycle($schoolYear)
            ->create();

        // Assign subjects to teachers
        $teachers->each(function ($teacher, $index) use ($subjects) {
            $teacher->subjects()->attach(
                $subjects->slice($index * 2, 2)->pluck('id')
            );
        });

        // 5. Create classes
        $classes = collect();
        foreach ([7, 8, 9] as $level) {
            foreach (['A', 'B'] as $suffix) {
                $classes->push(ClassRoom::create([
                    'school_year_id' => $schoolYear->id,
                    'name' => "{$level}{$suffix}",
                    'level' => $level,
                    'homeroom_teacher_id' => $teachers->random()->id,
                    'is_active' => true,
                ]));
            }
        }

        // 6. Create students and assign to classes
        $classes->each(function ($class) use ($schoolYear) {
            $students = StudentProfile::factory(10)->create();
            $class->students()->attach(
                $students->pluck('id'),
                ['school_year_id' => $schoolYear->id, 'is_active' => true]
            );
        });
    }
}
