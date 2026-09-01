<?php

namespace Database\Seeders;

use App\Enums\ProgramLevel;
use App\Models\AddressType;
use App\Models\Course;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Seeder;

/**
 * Departments, programs, address types and the shared course catalogue.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['PERM', 'Permanent address'],
            ['CURR', 'Current address'],
            ['MAIL', 'Mailing address'],
        ] as [$code, $name]) {
            AddressType::firstOrCreate(['code' => $code], ['name' => $name]);
        }

        $departments = [
            'FBM' => 'Faculty of Business and Management',
            'FCIT' => 'Faculty of Computing and Information Technology',
            'FENG' => 'Faculty of Engineering',
            'FHS' => 'Faculty of Health Sciences',
        ];

        foreach ($departments as $code => $name) {
            Department::firstOrCreate(['code' => $code], ['name' => $name]);
        }

        $programs = [
            ['FBM', 'BBA', 'BBA Business Administration', ProgramLevel::Bachelor, 4],
            ['FBM', 'BACC', 'BSc Accounting and Finance', ProgramLevel::Bachelor, 4],
            ['FCIT', 'BSCS', 'BSc Computer Science', ProgramLevel::Bachelor, 4],
            ['FCIT', 'BSIT', 'BSc Information Technology', ProgramLevel::Bachelor, 4],
            ['FENG', 'BENG', 'BEng Civil Engineering', ProgramLevel::Bachelor, 5],
            ['FHS', 'BNUR', 'BSc Nursing', ProgramLevel::Bachelor, 4],
        ];

        foreach ($programs as [$dept, $code, $name, $level, $years]) {
            Program::firstOrCreate(['code' => $code], [
                'department_id' => Department::where('code', $dept)->value('id'),
                'name' => $name,
                'level' => $level,
                'duration_years' => $years,
            ]);
        }

        // One catalogue serving both the attendance and submission modules.
        $courses = [
            ['FCIT', 'CS101', 'Introduction to Programming', 3],
            ['FCIT', 'CS201', 'Data Structures and Algorithms', 4],
            ['FCIT', 'CS210', 'Database Systems', 3],
            ['FCIT', 'IT230', 'Web Application Development', 3],
            ['FBM', 'BM101', 'Principles of Management', 3],
            ['FBM', 'AC150', 'Financial Accounting', 4],
            ['FENG', 'EN180', 'Engineering Mathematics', 4],
            ['FHS', 'HS120', 'Human Anatomy and Physiology', 4],
        ];

        foreach ($courses as [$dept, $code, $title, $credits]) {
            Course::firstOrCreate(['code' => $code], [
                'department_id' => Department::where('code', $dept)->value('id'),
                'title' => $title,
                'credit_hours' => $credits,
            ]);
        }
    }
}
