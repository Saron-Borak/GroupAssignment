<?php

namespace App\Services;

use App\Enums\ComplaintStatus;
use App\Enums\UserRole;
use App\Mail\ComplaintFiled;
use App\Models\Complaint;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Every write to the complaints table passes through here.
 *
 * A complaint now belongs to a student profile rather than to a local user
 * row, so the registry can see the complainant's full record when handling it.
 */
class ComplaintService
{
    /**
     * File a complaint on behalf of a student.
     *
     * @param  array<string, mixed>  $data
     */
    public function file(Student $student, array $data): Complaint
    {
        $complaint = DB::transaction(fn () => Complaint::create([
            'student_id' => $student->id,
            'reference' => $this->nextReference(),
            'category' => $data['category'],
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => ComplaintStatus::Pending,
        ]));

        $this->notifyRegistry($complaint);

        return $complaint;
    }

    /**
     * Tell the registry a case has arrived.
     *
     * Sent outside the transaction, and swallowed on failure: a mail server
     * that is down must not lose the student's complaint. The failure is
     * logged rather than shown, because the case itself was saved.
     */
    protected function notifyRegistry(Complaint $complaint): void
    {
        $recipients = User::where('role', UserRole::Admin)
            ->where('is_active', true)
            ->pluck('email')
            ->all();

        if ($recipients === []) {
            return;
        }

        try {
            Mail::to($recipients)->send(new ComplaintFiled($complaint->load('student.program')));
        } catch (Throwable $e) {
            Log::warning('Complaint notification not sent', [
                'reference' => $complaint->reference,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Withdraw a case entirely. Used by the registry for duplicates and cases
     * raised in error; there is no student-facing delete, so a student cannot
     * remove a case the registry is already handling.
     */
    public function delete(Complaint $complaint): void
    {
        $complaint->delete();
    }

    /**
     * Counts by status and by category over an optional date range, each in a
     * single grouped query rather than one count per bucket.
     *
     * @return array{total: int, by_status: array<string, int>, by_category: array<string, int>, resolved_rate: float}
     */
    public function summary(?string $from = null, ?string $to = null): array
    {
        $base = fn () => Complaint::query()
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        $byStatus = $base()->groupBy('status')
            ->selectRaw('status, COUNT(*) as total')
            ->pluck('total', 'status')
            ->all();

        $byCategory = $base()->groupBy('category')
            ->selectRaw('category, COUNT(*) as total')
            ->pluck('total', 'category')
            ->all();

        $total = array_sum($byStatus);
        $resolved = $byStatus[ComplaintStatus::Resolved->value] ?? 0;

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'by_category' => $byCategory,
            'resolved_rate' => $total > 0 ? round($resolved / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * Record the registry's response and move the case on.
     */
    public function respond(Complaint $complaint, User $handler, string $status, ?string $response): Complaint
    {
        $newStatus = ComplaintStatus::from($status);

        $complaint->update([
            'status' => $newStatus,
            'admin_response' => $response,
            'handled_by' => $handler->id,
            // Stamped only on the transition into Resolved, so reopening a case
            // clears the date rather than leaving a misleading one.
            'resolved_at' => $newStatus === ComplaintStatus::Resolved ? now() : null,
        ]);

        return $complaint->refresh();
    }

    /**
     * Sequential reference in the form CMP-00001.
     */
    protected function nextReference(): string
    {
        $last = Complaint::orderByDesc('id')->value('reference');
        $next = $last ? ((int) substr($last, -5)) + 1 : 1;

        return 'CMP-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
