<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Every write to the submission tables passes through here.
 *
 * On-time or late is decided by the server clock against the assignment
 * deadline, never by anything the student sends.
 */
class SubmissionService
{
    /**
     * Store or replace a student's submission for an assignment.
     */
    public function submit(Assignment $assignment, Student $student, UploadedFile $file): Submission
    {
        return DB::transaction(function () use ($assignment, $student, $file) {
            $existing = Submission::where('assignment_id', $assignment->id)
                ->where('student_id', $student->id)
                ->first();

            // Resubmitting replaces the file rather than accumulating copies.
            if ($existing) {
                Storage::disk('local')->delete($existing->file_path);
            }

            $path = $file->storeAs(
                'submissions/'.$assignment->id,
                Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
                'local',
            );

            $now = now();

            $attributes = [
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'submitted_at' => $now,
                'status' => $now->greaterThan($assignment->deadline)
                    ? SubmissionStatus::Late
                    : SubmissionStatus::OnTime,
            ];

            if ($existing) {
                $existing->update($attributes);

                return $existing->refresh();
            }

            return Submission::create($attributes + [
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
            ]);
        });
    }

    /**
     * Assignments issued to the sections this student is enrolled in, with
     * their submission attached where one exists.
     *
     * One query for the assignments and one for the submissions, rather than
     * one lookup per assignment.
     */
    public function assignmentsFor(Student $student)
    {
        $sectionIds = $student->enrollments()->active()->pluck('class_section_id');

        $assignments = Assignment::with('classSection.course')
            ->whereIn('class_section_id', $sectionIds)
            ->orderBy('deadline')
            ->get();

        $submissions = Submission::where('student_id', $student->id)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->get()
            ->keyBy('assignment_id');

        return $assignments->map(function (Assignment $a) use ($submissions) {
            $a->setRelation('studentSubmission', $submissions->get($a->id));

            return $a;
        });
    }
}
