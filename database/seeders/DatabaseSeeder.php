<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Complaint;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentAddress;
use App\Models\Submission;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReferenceDataSeeder::class,
            StudentProfileSeeder::class,
            IntegratedModuleSeeder::class,
        ]);

        $this->summarise();
    }

    protected function summarise(): void
    {
        $this->command?->newLine();
        $this->command?->info('EAMU Educational MIS - Student Profile Module seeded.');
        $this->command?->newLine();

        $this->command?->table(['Records', 'Count'], [
            ['Student profiles', Student::count()],
            ['Addresses', StudentAddress::count()],
            ['Guardians', Guardian::count()],
            ['Attendance records', AttendanceRecord::count()],
            ['Submissions', Submission::count()],
            ['Complaints', Complaint::count()],
        ]);

        $this->command?->line('  Sign in with any of these accounts (password: <fg=yellow>password</>)');
        $this->command?->newLine();

        $this->command?->table(['Role', 'Email'], [
            ['Registry administrator', 'registry@eamu.edu'],
            ['Faculty member', 'v.meas@eamu.edu'],
            ['Student', Student::whereNotNull('user_id')->orderBy('id')->first()?->email ?? '-'],
        ]);
    }
}
