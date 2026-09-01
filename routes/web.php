<?php

use App\Http\Controllers\Admin\AttendanceReportController;
use App\Http\Controllers\Admin\ClassSectionController as AdminClassSectionController;
use App\Http\Controllers\Admin\ComplaintController as AdminComplaintController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\UserAccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Faculty\DirectoryController;
use App\Http\Controllers\Lecturer\AssignmentController as LecturerAssignmentController;
use App\Http\Controllers\Lecturer\AttendanceController;
use App\Http\Controllers\Lecturer\ClassSectionController as LecturerClassSectionController;
use App\Http\Controllers\Student\AssignmentController as StudentAssignmentController;
use App\Http\Controllers\Student\AttendanceController as StudentAttendanceController;
use App\Http\Controllers\Student\CheckInController;
use App\Http\Controllers\Student\ClassSectionController as StudentClassSectionController;
use App\Http\Controllers\Student\ComplaintController as StudentComplaintController;
use App\Http\Controllers\Student\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Signed in
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', fn () => redirect()->route(auth()->user()->role->homeRoute()))->name('home');

    /* ---------------------------- Registry --------------------------------- */
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        // The Student Profile module.
        Route::resource('students', StudentController::class);
        Route::put('students/{id}/restore', [StudentController::class, 'restore'])->name('students.restore');
        Route::post('students/{student}/account', [UserAccountController::class, 'issue'])->name('students.account');

        Route::resource('programs', ProgramController::class)->except('show');
        Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        // The shared catalogue and roster that both the attendance and the
        // submission module read from.
        Route::resource('courses', CourseController::class)->except('show');
        Route::resource('sections', AdminClassSectionController::class);
        Route::post('sections/{section}/schedules', [AdminClassSectionController::class, 'storeSchedule'])->name('sections.schedules.store');
        Route::delete('sections/{section}/schedules/{schedule}', [AdminClassSectionController::class, 'destroySchedule'])->name('sections.schedules.destroy');
        Route::post('sections/{section}/enroll', [AdminClassSectionController::class, 'enroll'])->name('sections.enroll');
        Route::delete('sections/{section}/enroll/{enrollment}', [AdminClassSectionController::class, 'unenroll'])->name('sections.unenroll');

        Route::get('users', [UserAccountController::class, 'index'])->name('users.index');
        Route::put('users/{user}/password', [UserAccountController::class, 'resetPassword'])->name('users.password');
        Route::put('users/{user}/toggle', [UserAccountController::class, 'toggleActive'])->name('users.toggle');

        Route::get('reports/by-program', [ReportController::class, 'byProgram'])->name('reports.by-program');
        Route::get('reports/by-program/export', [ReportController::class, 'exportByProgram'])->name('reports.by-program.export');
        Route::get('reports/completeness', [ReportController::class, 'completeness'])->name('reports.completeness');

        // Attendance module - registry reporting.
        Route::get('reports/attendance', [AttendanceReportController::class, 'overview'])->name('reports.attendance');
        Route::get('reports/attendance/export', [AttendanceReportController::class, 'exportOverview'])->name('reports.attendance.export');
        Route::get('reports/at-risk', [AttendanceReportController::class, 'atRisk'])->name('reports.at-risk');
        Route::get('reports/at-risk/export', [AttendanceReportController::class, 'exportAtRisk'])->name('reports.at-risk.export');

        // Complaint module - registry side. The report route is declared before
        // the wildcard, or "report" would be read as a complaint identifier.
        Route::get('complaints', [AdminComplaintController::class, 'index'])->name('complaints.index');
        Route::get('complaints/report', [AdminComplaintController::class, 'report'])->name('complaints.report');
        Route::get('complaints/report/export', [AdminComplaintController::class, 'exportReport'])->name('complaints.report.export');
        Route::get('complaints/{complaint}', [AdminComplaintController::class, 'show'])->name('complaints.show');
        Route::put('complaints/{complaint}', [AdminComplaintController::class, 'respond'])->name('complaints.respond');
        Route::delete('complaints/{complaint}', [AdminComplaintController::class, 'destroy'])->name('complaints.destroy');
    });

    /* ---------------------------- Faculty ---------------------------------- */
    Route::middleware('role:faculty')->prefix('faculty')->name('faculty.')->group(function () {
        Route::get('dashboard', [DirectoryController::class, 'dashboard'])->name('dashboard');
        Route::get('students/{student}', [DirectoryController::class, 'show'])->name('students.show');

        // My classes: the timetable, roster, register history and assignments,
        // all on one screen.
        Route::get('classes', [LecturerClassSectionController::class, 'index'])->name('sections.index');
        Route::get('classes/{section}', [LecturerClassSectionController::class, 'show'])->name('sections.show');

        // Attendance module - taken against the shared enrolment roster.
        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
        Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::post('attendance/generate', [AttendanceController::class, 'generate'])->name('attendance.generate');
        Route::get('attendance/{session}/edit', [AttendanceController::class, 'edit'])->name('attendance.edit');
        Route::put('attendance/{session}', [AttendanceController::class, 'update'])->name('attendance.update');
        Route::delete('attendance/{session}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
        Route::get('attendance/{session}/mark', [AttendanceController::class, 'mark'])->name('attendance.mark');
        Route::put('attendance/{session}/mark', [AttendanceController::class, 'storeMarks'])->name('attendance.mark.store');

        // Session lifecycle and the QR kiosk.
        Route::put('attendance/{session}/open', [AttendanceController::class, 'open'])->name('attendance.open');
        Route::put('attendance/{session}/close', [AttendanceController::class, 'close'])->name('attendance.close');
        Route::get('attendance/{session}/qr', [AttendanceController::class, 'qr'])->name('attendance.qr');
        Route::get('attendance/{session}/qr/refresh', [AttendanceController::class, 'qrRefresh'])->name('attendance.qr.refresh');

        Route::get('classes/{section}/report', [AttendanceController::class, 'report'])->name('attendance.report');
        Route::get('classes/{section}/report/export', [AttendanceController::class, 'exportReport'])->name('attendance.report.export');

        // Project submission module - lecturer side.
        Route::get('assignments', [LecturerAssignmentController::class, 'index'])->name('assignments.index');
        Route::get('assignments/create', [LecturerAssignmentController::class, 'create'])->name('assignments.create');
        Route::post('assignments', [LecturerAssignmentController::class, 'store'])->name('assignments.store');
        Route::get('assignments/{assignment}', [LecturerAssignmentController::class, 'show'])->name('assignments.show');
        Route::get('assignments/{assignment}/edit', [LecturerAssignmentController::class, 'edit'])->name('assignments.edit');
        Route::put('assignments/{assignment}', [LecturerAssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('assignments/{assignment}', [LecturerAssignmentController::class, 'destroy'])->name('assignments.destroy');
        Route::get('assignments/{assignment}/download/{studentId}', [LecturerAssignmentController::class, 'download'])->name('assignments.download');
    });

    /* ---------------------------- Student ---------------------------------- */
    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('profile', [ProfileController::class, 'show'])->name('profile');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        // My classes, and the catalogue of ones still open.
        Route::get('classes', [StudentClassSectionController::class, 'index'])->name('sections.index');
        Route::get('classes/browse', [StudentClassSectionController::class, 'browse'])->name('sections.browse');
        Route::post('classes/{section}/enroll', [StudentClassSectionController::class, 'enroll'])->name('sections.enroll');
        Route::delete('classes/{section}/enroll', [StudentClassSectionController::class, 'unenroll'])->name('sections.unenroll');

        // Attendance module - student side.
        Route::get('attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/{section}', [StudentAttendanceController::class, 'show'])->name('attendance.show');
        Route::get('check-in', [CheckInController::class, 'form'])->name('checkin.form');
        Route::post('check-in', [CheckInController::class, 'submit'])->name('checkin.submit');

        // Project submission module - student side.
        Route::get('assignments', [StudentAssignmentController::class, 'index'])->name('assignments.index');
        Route::get('assignments/{assignment}', [StudentAssignmentController::class, 'show'])->name('assignments.show');
        Route::post('assignments/{assignment}/submit', [StudentAssignmentController::class, 'store'])->name('assignments.submit');
        Route::get('assignments/{assignment}/download', [StudentAssignmentController::class, 'download'])->name('assignments.download');

        // Complaint module - student side.
        Route::get('complaints', [StudentComplaintController::class, 'index'])->name('complaints.index');
        Route::post('complaints', [StudentComplaintController::class, 'store'])->name('complaints.store');
        Route::get('complaints/{complaint}', [StudentComplaintController::class, 'show'])->name('complaints.show');
    });

    /*
     * The QR landing route.
     *
     * Sits outside the /student prefix because it is what a phone camera opens,
     * and it is guarded by the student role rather than the prefix. A signed-out
     * student is sent to login and returned here by the intended-URL mechanism,
     * which is exactly the path a phone that has never signed in takes.
     */
    Route::middleware('role:student')
        ->get('check-in/{token}', [CheckInController::class, 'scan'])
        ->name('checkin.scan');
});
