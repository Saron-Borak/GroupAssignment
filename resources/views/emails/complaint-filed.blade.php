@php($student = $complaint->student)
<h2 style="font-family: system-ui, sans-serif; margin: 0 0 12px;">
    New complaint {{ $complaint->reference }}
</h2>

<p style="font-family: system-ui, sans-serif; margin: 0 0 16px; color: #475569;">
    A student has raised a case through the complaint module. It is waiting in the
    registry queue.
</p>

<table style="font-family: system-ui, sans-serif; border-collapse: collapse; font-size: 14px;">
    <tr>
        <td style="padding: 4px 16px 4px 0; color: #64748b;">Student</td>
        <td style="padding: 4px 0;">
            {{ $student->fullName() }} ({{ $student->student_id_no }})
        </td>
    </tr>
    <tr>
        <td style="padding: 4px 16px 4px 0; color: #64748b;">Programme</td>
        <td style="padding: 4px 0;">{{ $student->program?->name ?? 'Not recorded' }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 16px 4px 0; color: #64748b;">Email</td>
        <td style="padding: 4px 0;">{{ $student->email }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 16px 4px 0; color: #64748b;">Category</td>
        <td style="padding: 4px 0;">{{ $complaint->category->label() }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 16px 4px 0; color: #64748b;">Subject</td>
        <td style="padding: 4px 0;">{{ $complaint->title }}</td>
    </tr>
</table>

<p style="font-family: system-ui, sans-serif; margin: 16px 0 8px; color: #0f172a; font-weight: 600;">
    What the student reported
</p>
<p style="font-family: system-ui, sans-serif; margin: 0 0 20px; color: #334155; white-space: pre-line;">{{ $complaint->description }}</p>

<p style="font-family: system-ui, sans-serif; margin: 0;">
    <a href="{{ route('admin.complaints.show', $complaint) }}"
       style="background: #1d4ed8; color: #fff; padding: 10px 18px; border-radius: 6px; text-decoration: none;">
        Open the case
    </a>
</p>
