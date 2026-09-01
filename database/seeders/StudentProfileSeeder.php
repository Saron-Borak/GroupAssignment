<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\GuardianRelationship;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\AddressType;
use App\Models\Guardian;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentAddress;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Staff accounts and the student body, with addresses and guardians.
 *
 * A deliberate minority of profiles are left incomplete so the completeness
 * report has real work to show.
 */
class StudentProfileSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public const STUDENT_COUNT = 45;

    public function run(): void
    {
        mt_srand(20260901);

        $password = Hash::make(self::PASSWORD);

        User::updateOrCreate(['email' => 'registry@eamu.edu'], [
            'name' => 'Sokha Chan',
            'password' => $password,
            'role' => UserRole::Admin,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $faculty = [
            ['Dr. Vannak Meas', 'v.meas@eamu.edu'],
            ['Dr. Sreymom Pich', 's.pich@eamu.edu'],
            ['Prof. Chanthou Nou', 'c.nou@eamu.edu'],
            ['Ms. Dara Kim', 'd.kim@eamu.edu'],
        ];

        foreach ($faculty as [$name, $email]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => $password,
                'role' => UserRole::Faculty,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        $this->seedStudents($password);
    }

    protected function seedStudents(string $password): void
    {
        if (Student::count() >= self::STUDENT_COUNT) {
            return;
        }

        $given = ['Sophea', 'Panha', 'Chanda', 'Vichea', 'Kanya', 'Rasmey', 'Sokun', 'Theary',
            'Makara', 'Sovann', 'Pisey', 'Nita', 'Samnang', 'Leakhena', 'Kosal'];
        $family = ['Chea', 'Sok', 'Nguon', 'Heng', 'Ros', 'Ouk', 'Yim', 'Sam', 'Khoun', 'Prak'];
        $cities = ['Phnom Penh', 'Siem Reap', 'Battambang', 'Kampot', 'Sihanoukville'];

        $programs = Program::pluck('id')->all();
        $permanent = AddressType::where('code', 'PERM')->value('id');
        $current = AddressType::where('code', 'CURR')->value('id');
        $seen = [];

        for ($i = 1; $i <= self::STUDENT_COUNT; $i++) {
            // The intdiv term breaks the cycle: without it the two lists share a
            // period and the whole cohort ends up with only two surnames.
            $first = $given[($i * 7) % count($given)];
            $last = $family[($i * 3 + intdiv($i, count($given))) % count($family)];
            $slug = Str::of("{$first}.{$last}")->lower()->toString();
            $n = $seen[$slug] = ($seen[$slug] ?? 0) + 1;
            $email = $slug.($n > 1 ? ".{$n}" : '').'@student.eamu.edu';

            $intake = 2023 + ($i % 4);

            $student = Student::create([
                'program_id' => $programs[$i % count($programs)],
                'student_id_no' => "EAMU-{$intake}-".str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'first_name' => $first,
                'last_name' => $last,
                'gender' => $i % 2 ? Gender::Female : Gender::Male,
                'date_of_birth' => now()->subYears(18 + ($i % 6))->subDays($i * 11)->toDateString(),
                'nationality' => 'Cambodian',
                'national_id' => str_pad((string) (100000000 + $i * 137), 10, '0', STR_PAD_LEFT),
                'email' => $email,
                // Every fifth profile is missing a phone number.
                'phone' => $i % 5 === 0 ? null : '01'.(($i % 9) + 1).' '.str_pad((string) (100 + $i), 3, '0', STR_PAD_LEFT).' '.str_pad((string) (200 + $i), 3, '0', STR_PAD_LEFT),
                'intake_year' => $intake,
                'admission_date' => "{$intake}-09-01",
                'status' => StudentStatus::Active,
            ]);

            // A sign-in account for roughly two thirds of the cohort.
            if ($i % 3 !== 0) {
                $user = User::create([
                    'name' => $student->fullName(),
                    'email' => $email,
                    'password' => $password,
                    'role' => UserRole::Student,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
                $student->update(['user_id' => $user->id]);
            }

            // Every fourth profile has no address recorded.
            if ($i % 4 !== 0) {
                StudentAddress::create([
                    'student_id' => $student->id,
                    'address_type_id' => $permanent,
                    'line1' => 'House '.(10 + $i).', Street '.(100 + ($i * 3) % 400),
                    'city' => $cities[$i % count($cities)],
                    'province' => $cities[$i % count($cities)],
                    'postal_code' => (string) (12000 + $i),
                    'country' => 'Cambodia',
                    'is_primary' => true,
                ]);

                if ($i % 6 === 0) {
                    StudentAddress::create([
                        'student_id' => $student->id,
                        'address_type_id' => $current,
                        'line1' => 'Room '.(200 + $i).', University Hall',
                        'city' => 'Phnom Penh',
                        'province' => 'Phnom Penh',
                        'country' => 'Cambodia',
                        'is_primary' => false,
                    ]);
                }
            }

            // Every seventh profile has no guardian recorded.
            if ($i % 7 !== 0) {
                Guardian::create([
                    'student_id' => $student->id,
                    'full_name' => $given[($i * 3) % count($given)].' '.$last,
                    'relationship' => $i % 2 ? GuardianRelationship::Mother : GuardianRelationship::Father,
                    'phone' => '012 '.str_pad((string) (300 + $i), 3, '0', STR_PAD_LEFT).' '.str_pad((string) (400 + $i), 3, '0', STR_PAD_LEFT),
                    'email' => $i % 3 === 0 ? null : "guardian{$i}@example.com",
                    'occupation' => ['Teacher', 'Farmer', 'Shopkeeper', 'Engineer', 'Nurse'][$i % 5],
                    'is_emergency_contact' => true,
                ]);
            }
        }
    }
}
