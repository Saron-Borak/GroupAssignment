@php($user = auth()->user())
<aside class="app-sidebar">
    <div class="d-flex align-items-center gap-2 px-3 py-3 border-bottom border-light border-opacity-10">
        <span class="brand-mark">{{ config('mis.university_short_name') }}</span>
        <div class="lh-sm text-white">
            <div class="fw-semibold" style="font-size:.9rem">Educational MIS</div>
            <div class="text-white-50" style="font-size:.68rem">Student Profile Module</div>
        </div>
    </div>

    <nav class="nav flex-column px-2 pb-4">
        @if($user->isAdmin())
            <div class="nav-section">Overview</div>
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>

            <div class="nav-section">Student Profile</div>
            <a class="nav-link {{ request()->routeIs('admin.students.index') || request()->routeIs('admin.students.show') || request()->routeIs('admin.students.edit') ? 'active' : '' }}"
               href="{{ route('admin.students.index') }}">
                <i class="bi bi-people me-2"></i>Student profiles
            </a>
            <a class="nav-link {{ request()->routeIs('admin.students.create') ? 'active' : '' }}" href="{{ route('admin.students.create') }}">
                <i class="bi bi-person-plus me-2"></i>Add a student
            </a>

            <div class="nav-section">Academic structure</div>
            <a class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}" href="{{ route('admin.departments.index') }}">
                <i class="bi bi-building me-2"></i>Departments
            </a>
            <a class="nav-link {{ request()->routeIs('admin.programs.*') ? 'active' : '' }}" href="{{ route('admin.programs.index') }}">
                <i class="bi bi-diagram-3 me-2"></i>Programs
            </a>
            <a class="nav-link {{ request()->routeIs('admin.courses.*') ? 'active' : '' }}" href="{{ route('admin.courses.index') }}">
                <i class="bi bi-journal-bookmark me-2"></i>Courses
            </a>
            <a class="nav-link {{ request()->routeIs('admin.sections.*') ? 'active' : '' }}" href="{{ route('admin.sections.index') }}">
                <i class="bi bi-collection me-2"></i>Class sections
            </a>

            <div class="nav-section">Modules</div>
            <a class="nav-link {{ request()->routeIs('admin.reports.attendance') ? 'active' : '' }}" href="{{ route('admin.reports.attendance') }}">
                <i class="bi bi-calendar-check me-2"></i>Attendance
            </a>
            <a class="nav-link {{ request()->routeIs('admin.reports.at-risk') ? 'active' : '' }}" href="{{ route('admin.reports.at-risk') }}">
                <i class="bi bi-exclamation-triangle me-2"></i>Students at risk
            </a>
            <a class="nav-link {{ request()->routeIs('admin.complaints.index') || request()->routeIs('admin.complaints.show') ? 'active' : '' }}" href="{{ route('admin.complaints.index') }}">
                <i class="bi bi-chat-left-text me-2"></i>Complaints
            </a>
            <a class="nav-link {{ request()->routeIs('admin.complaints.report') ? 'active' : '' }}" href="{{ route('admin.complaints.report') }}">
                <i class="bi bi-pie-chart me-2"></i>Complaint summary
            </a>

            <div class="nav-section">Profile reports</div>
            <a class="nav-link {{ request()->routeIs('admin.reports.by-program') ? 'active' : '' }}" href="{{ route('admin.reports.by-program') }}">
                <i class="bi bi-list-columns me-2"></i>Students by program
            </a>
            <a class="nav-link {{ request()->routeIs('admin.reports.completeness') ? 'active' : '' }}" href="{{ route('admin.reports.completeness') }}">
                <i class="bi bi-clipboard-check me-2"></i>Profile completeness
            </a>

            <div class="nav-section">Accounts</div>
            <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                <i class="bi bi-shield-lock me-2"></i>User accounts
            </a>

        @elseif($user->isFaculty())
            <div class="nav-section">Teaching</div>
            <a class="nav-link {{ request()->routeIs('faculty.dashboard') || request()->routeIs('faculty.students.*') ? 'active' : '' }}" href="{{ route('faculty.dashboard') }}">
                <i class="bi bi-people me-2"></i>My students
            </a>
            <a class="nav-link {{ request()->routeIs('faculty.sections.*') ? 'active' : '' }}" href="{{ route('faculty.sections.index') }}">
                <i class="bi bi-collection me-2"></i>My classes
            </a>

            <div class="nav-section">Modules</div>
            <a class="nav-link {{ request()->routeIs('faculty.attendance.*') ? 'active' : '' }}" href="{{ route('faculty.attendance.index') }}">
                <i class="bi bi-calendar-check me-2"></i>Attendance
            </a>
            <a class="nav-link {{ request()->routeIs('faculty.assignments.*') ? 'active' : '' }}" href="{{ route('faculty.assignments.index') }}">
                <i class="bi bi-cloud-arrow-up me-2"></i>Assignments
            </a>
        @else
            <div class="nav-section">My record</div>
            <a class="nav-link {{ request()->routeIs('student.profile') ? 'active' : '' }}" href="{{ route('student.profile') }}">
                <i class="bi bi-person-badge me-2"></i>My profile
            </a>
            <a class="nav-link {{ request()->routeIs('student.sections.*') ? 'active' : '' }}" href="{{ route('student.sections.index') }}">
                <i class="bi bi-collection me-2"></i>My classes
            </a>

            <div class="nav-section">Modules</div>
            <a class="nav-link {{ request()->routeIs('student.attendance.*') ? 'active' : '' }}" href="{{ route('student.attendance.index') }}">
                <i class="bi bi-calendar-check me-2"></i>My attendance
            </a>
            <a class="nav-link {{ request()->routeIs('student.checkin.*') ? 'active' : '' }}" href="{{ route('student.checkin.form') }}">
                <i class="bi bi-qr-code-scan me-2"></i>Check in
            </a>
            <a class="nav-link {{ request()->routeIs('student.assignments.*') ? 'active' : '' }}" href="{{ route('student.assignments.index') }}">
                <i class="bi bi-cloud-arrow-up me-2"></i>My assignments
            </a>
            <a class="nav-link {{ request()->routeIs('student.complaints.*') ? 'active' : '' }}" href="{{ route('student.complaints.index') }}">
                <i class="bi bi-chat-left-text me-2"></i>My complaints
            </a>
        @endif
    </nav>
</aside>
