<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\MarkedVia;
use App\Enums\SessionStatus;
use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSchedule;
use App\Models\ClassSection;
use App\Models\Complaint;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Data for the three subsystems that were built as separate mini projects and
 * are now integrated behind the shared student profile.
 *
 * Every row here references students.id, which is the whole point: before
 * integration, each system carried its own copy of the student's identity.
 */
class IntegratedModuleSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(20260901);

        $lecturers = User::where('role', UserRole::Faculty)->pluck('id')->all();
        $students = Student::orderBy('id')->get();

        if ($lecturers === [] || $students->isEmpty()) {
            return;
        }

        $sections = $this->seedSections($lecturers);
        $this->seedEnrollments($sections, $students);
        $this->seedAttendance($sections);
        $this->seedSubmissions($sections);
        $this->seedComplaints($students);
    }

    /** @return Collection<int, ClassSection> */
    protected function seedSections(array $lecturers): Collection
    {
        $sections = collect();

        // Two slots a week, staggered so a student on several classes does not
        // end up with every one of them at the same hour.
        $slots = [
            [[1, '08:00', '10:00'], [3, '13:00', '15:00']],
            [[2, '10:00', '12:00'], [4, '08:00', '10:00']],
            [[1, '13:00', '15:00'], [5, '10:00', '12:00']],
            [[3, '08:00', '10:00'], [5, '13:00', '15:00']],
        ];

        foreach (Course::orderBy('id')->get() as $i => $course) {
            $room = chr(65 + ($i % 4)).'-'.(101 + $i * 7);

            $section = ClassSection::firstOrCreate([
                'course_id' => $course->id,
                'term' => '2026 Semester 2',
                'section_code' => 'A',
            ], [
                'lecturer_id' => $lecturers[$i % count($lecturers)],
                'room' => $room,
            ]);

            if ($section->schedules()->doesntExist()) {
                foreach ($slots[$i % count($slots)] as [$day, $start, $end]) {
                    ClassSchedule::create([
                        'class_section_id' => $section->id,
                        'day_of_week' => $day,
                        'start_time' => $start.':00',
                        'end_time' => $end.':00',
                        'room' => $room,
                    ]);
                }
            }

            $sections->push($section);
        }

        return $sections;
    }

    protected function seedEnrollments(Collection $sections, Collection $students): void
    {
        if (Enrollment::exists()) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($students as $index => $student) {
            // Four classes each, spread so that no section is left empty.
            for ($n = 0; $n < 4; $n++) {
                $section = $sections[($index * 3 + $n * 2) % $sections->count()];

                $rows[$student->id.':'.$section->id] = [
                    'class_section_id' => $section->id,
                    'student_id' => $student->id,
                    'status' => EnrollmentStatus::Enrolled->value,
                    'enrolled_at' => $now->copy()->subMonths(3)->toDateString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            Enrollment::insert($chunk);
        }
    }

    protected function seedAttendance(Collection $sections): void
    {
        if (AttendanceSession::exists()) {
            return;
        }

        $now = now();

        foreach ($sections as $section) {
            $roster = $section->enrollments()->active()->pluck('student_id');

            if ($roster->isEmpty()) {
                continue;
            }

            $rows = [];
            $slot = $section->schedules()->orderBy('day_of_week')->first();
            $start = $slot?->start_time ?? '08:00:00';
            $end = $slot?->end_time ?? '10:00:00';

            // Twelve past meetings per section, all closed and marked.
            for ($w = 1; $w <= 12; $w++) {
                $session = AttendanceSession::create([
                    'class_section_id' => $section->id,
                    'session_date' => $now->copy()->subWeeks($w)->toDateString(),
                    'start_time' => $start,
                    'end_time' => $end,
                    'topic' => "Week {$w}",
                    'status' => SessionStatus::Closed,
                    'closed_at' => $now->copy()->subWeeks($w)->setTimeFromTimeString($end),
                ]);

                foreach ($roster as $studentId) {
                    $status = $this->rollAttendance($studentId);

                    $rows[] = [
                        'attendance_session_id' => $session->id,
                        'student_id' => $studentId,
                        'status' => $status->value,
                        // Most registers were called by the lecturer; a share of
                        // the present marks came from students scanning the code.
                        'marked_via' => $status->countsAsAttended() && ($studentId + $w) % 3 === 0
                            ? MarkedVia::Qr->value
                            : MarkedVia::Manual->value,
                        'marked_at' => $now->copy()->subWeeks($w),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // One meeting still ahead, so a lecturer can open it and demonstrate
            // QR check-in without having to create a session first.
            AttendanceSession::create([
                'class_section_id' => $section->id,
                'session_date' => $now->copy()->addDay()->toDateString(),
                'start_time' => $start,
                'end_time' => $end,
                'topic' => 'Week 13',
                'status' => SessionStatus::Scheduled,
            ]);

            foreach (array_chunk($rows, 500) as $chunk) {
                AttendanceRecord::insert($chunk);
            }
        }
    }

    /**
     * Roughly one student in eight is seeded below the attendance threshold, so
     * the at-risk indicators on the profile are not uniformly green.
     */
    protected function rollAttendance(int $studentId): AttendanceStatus
    {
        $roll = mt_rand(1, 100);

        if ($studentId % 8 === 0) {
            return match (true) {
                $roll <= 55 => AttendanceStatus::Present,
                $roll <= 62 => AttendanceStatus::Late,
                $roll <= 97 => AttendanceStatus::Absent,
                default => AttendanceStatus::Excused,
            };
        }

        return match (true) {
            $roll <= 84 => AttendanceStatus::Present,
            $roll <= 91 => AttendanceStatus::Late,
            $roll <= 98 => AttendanceStatus::Absent,
            default => AttendanceStatus::Excused,
        };
    }

    protected function seedSubmissions(Collection $sections): void
    {
        if (Assignment::exists()) {
            return;
        }

        $now = now();
        $numbers = Student::pluck('student_id_no', 'id');

        foreach ($sections as $section) {
            $roster = $section->enrollments()->active()->pluck('student_id');

            if ($roster->isEmpty()) {
                continue;
            }

            // Two closed assignments and one still open, so the demonstration can
            // show a submission being accepted on time rather than always late.
            $deadlines = [1 => -14, 2 => -4, 3 => 7];

            for ($a = 1; $a <= 3; $a++) {
                $open = $deadlines[$a] > 0;

                $assignment = Assignment::create([
                    'class_section_id' => $section->id,
                    'title' => "Assignment {$a}: ".$section->course->title,
                    'description' => 'Submit your work as a single PDF before the deadline.',
                    'deadline' => $now->copy()->addDays($deadlines[$a])->setTime(23, 59),
                ]);

                $rows = [];

                foreach ($roster as $studentId) {
                    // Roughly one student in six does not submit at all. On the open
                    // assignment most of the roster has not submitted yet.
                    if (($studentId + $a) % ($open ? 2 : 6) === 0) {
                        continue;
                    }

                    $late = ! $open && ($studentId + $a) % 5 === 0;
                    $number = $numbers[$studentId] ?? "STU-{$studentId}";
                    $path = 'submissions/seed-'.$assignment->id.'-'.$studentId.'.pdf';

                    // Write a real file, so Download on a seeded row returns a document
                    // rather than the 404 a dangling file_path would produce.
                    Storage::disk('local')->put($path, $this->placeholderPdf(
                        $assignment->title,
                        $number.' - '.$section->course->code.' '.$section->section_code,
                    ));

                    $rows[] = [
                        'assignment_id' => $assignment->id,
                        'student_id' => $studentId,
                        'file_path' => $path,
                        'original_filename' => "{$number}-assignment-{$a}.pdf",
                        'submitted_at' => $open
                            ? $now->copy()->subHours(($studentId % 5) + 2)
                            : $assignment->deadline->copy()->addHours($late ? 30 : -20),
                        'status' => ($late ? SubmissionStatus::Late : SubmissionStatus::OnTime)->value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($rows, 500) as $chunk) {
                    Submission::insert($chunk);
                }
            }
        }
    }

    /**
     * A one-page PDF, assembled by hand so the seeder needs no PDF library.
     * Offsets in the cross-reference table are counted as the file is built.
     */
    protected function placeholderPdf(string $title, string $subtitle): string
    {
        $escape = fn (string $v) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $v);

        $content = 'BT /F1 18 Tf 60 760 Td ('.$escape($title).") Tj ET\n"
            .'BT /F1 12 Tf 60 730 Td ('.$escape($subtitle).") Tj ET\n"
            ."BT /F1 10 Tf 60 700 Td (Placeholder document written by the EAMU MIS demo seeder.) Tj ET\n";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] '
                .'/Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($content)." >>\nstream\n".$content.'endstream',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $i => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$body."\nendobj\n";
        }

        $startxref = strlen($pdf);
        $size = count($objects) + 1;

        $pdf .= "xref\n0 {$size}\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        return $pdf."trailer\n<< /Size {$size} /Root 1 0 R >>\nstartxref\n{$startxref}\n%%EOF\n";
    }

    protected function seedComplaints(Collection $students): void
    {
        if (Complaint::exists()) {
            return;
        }

        // Category, subject and the registry's reply travel together, so a seeded
        // case reads as one coherent story rather than three unrelated columns.
        $cases = [
            [
                ComplaintCategory::Facility,
                'Air conditioning not working in room B-204',
                'The room has been uncomfortably hot for the last two weeks of lectures.',
                'Facilities replaced the compressor on 26 August. Please report any recurrence.',
            ],
            [
                ComplaintCategory::Facility,
                'Library closes too early during examination week',
                'The library shuts at 18:00 while evening classes finish at 20:00.',
                'Opening hours are extended to 21:00 for the whole examination period.',
            ],
            [
                ComplaintCategory::Academic,
                'Course materials not uploaded on time',
                'Week 5 slides were still missing when the tutorial took place.',
                'The lecturer has been reminded and the outstanding material is now posted.',
            ],
            [
                ComplaintCategory::Administrative,
                'Requesting a change of tutorial group',
                'My tutorial clashes with a compulsory laboratory session in another course.',
                'You have been moved to the Thursday group with effect from next week.',
            ],
            [
                ComplaintCategory::Facility,
                'Printer in the computer laboratory is out of order',
                'The laboratory printer has been jammed since the start of the month.',
                'A replacement unit was installed on 28 August.',
            ],
            [
                ComplaintCategory::Administrative,
                'Enrolment record shows the wrong programme',
                'My profile lists the wrong degree programme, so my timetable is incorrect.',
                'The registry has corrected the programme on your profile.',
            ],
            [
                ComplaintCategory::Other,
                'Campus shuttle is consistently late in the morning',
                'The 07:15 shuttle has arrived after 08:00 on most days this month.',
                'An extra morning departure has been added to the shuttle timetable.',
            ],
        ];

        $n = 0;

        foreach ($students as $index => $student) {
            // Roughly one student in four has raised a case.
            if ($index % 4 !== 0) {
                continue;
            }

            $n++;
            [$category, $title, $description, $reply] = $cases[$n % count($cases)];
            $resolved = $n % 3 === 0;

            Complaint::create([
                'student_id' => $student->id,
                'reference' => 'CMP-'.str_pad((string) $n, 5, '0', STR_PAD_LEFT),
                'category' => $category,
                'title' => $title,
                'description' => $description,
                'status' => $resolved
                    ? ComplaintStatus::Resolved
                    : ($n % 2 ? ComplaintStatus::Pending : ComplaintStatus::InProgress),
                'admin_response' => $resolved ? $reply : null,
                'resolved_at' => $resolved ? now()->subDays(3) : null,
            ]);
        }
    }
}
