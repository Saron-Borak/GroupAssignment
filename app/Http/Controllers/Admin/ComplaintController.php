<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Services\ComplaintService;
use App\Support\CsvExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The complaint module, registry side.
 *
 * Because a complaint now references a student profile rather than a local
 * user row, the registry can see who raised it and how they are doing across
 * the other modules while handling the case.
 */
class ComplaintController extends Controller
{
    public function __construct(protected ComplaintService $complaints) {}

    public function index(Request $request): View
    {
        $complaints = Complaint::with('student.program')
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', ComplaintStatus::from($request->string('status')->toString())),
            )
            ->when(
                $request->filled('category'),
                fn ($q) => $q->where('category', ComplaintCategory::from($request->string('category')->toString())),
            )
            ->when($request->string('q')->toString(), fn ($q, $term) => $q->where(
                fn ($inner) => $inner
                    ->where('reference', 'like', "%{$term}%")
                    ->orWhere('title', 'like', "%{$term}%")
            ))
            // Open cases first, then newest. A CASE expression rather than
            // MySQL's FIELD(), so the same query runs on SQLite under test.
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'in_progress' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.complaints.index', [
            'complaints' => $complaints,
            'counts' => [
                'pending' => Complaint::where('status', ComplaintStatus::Pending)->count(),
                'in_progress' => Complaint::where('status', ComplaintStatus::InProgress)->count(),
                'resolved' => Complaint::where('status', ComplaintStatus::Resolved)->count(),
            ],
        ]);
    }

    public function show(Complaint $complaint): View
    {
        $complaint->load(['student.program', 'handler']);

        return view('admin.complaints.show', compact('complaint'));
    }

    public function respond(Request $request, Complaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', new Enum(ComplaintStatus::class)],
            'admin_response' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->complaints->respond(
            $complaint,
            $request->user(),
            $validated['status'],
            $validated['admin_response'] ?? null,
        );

        return back()->with('success', "Case {$complaint->reference} updated.");
    }

    /**
     * Withdraw a case. Restricted to the registry: a student who could delete
     * their own case could erase the record of one already being handled.
     */
    public function destroy(Complaint $complaint): RedirectResponse
    {
        $reference = $complaint->reference;

        $this->complaints->delete($complaint);

        return redirect()->route('admin.complaints.index')
            ->with('success', "Case {$reference} was withdrawn.");
    }

    /**
     * Counts by status and by category, over an optional date range.
     */
    public function report(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        return view('admin.complaints.report', [
            'summary' => $this->complaints->summary($from, $to),
            'from' => $from,
            'to' => $to,
            'statuses' => ComplaintStatus::cases(),
            'categories' => ComplaintCategory::cases(),
        ]);
    }

    public function exportReport(Request $request, CsvExporter $csv): StreamedResponse
    {
        $summary = $this->complaints->summary(
            $request->date('from')?->toDateString(),
            $request->date('to')?->toDateString(),
        );

        $rows = collect();

        foreach (ComplaintStatus::cases() as $case) {
            $rows->push(['Status', $case->label(), $summary['by_status'][$case->value] ?? 0]);
        }

        foreach (ComplaintCategory::cases() as $case) {
            $rows->push(['Category', $case->label(), $summary['by_category'][$case->value] ?? 0]);
        }

        $rows->push(['Total', 'All cases', $summary['total']]);

        return $csv->download(
            $csv->filename('complaints-summary'),
            ['Grouping', 'Value', 'Cases'],
            $rows,
        );
    }
}
