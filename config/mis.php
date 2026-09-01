<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Institution
    |--------------------------------------------------------------------------
    */

    'university_name' => 'East Asia Management University',
    'university_short_name' => 'EAMU',
    'system_name' => 'Educational Management Information System',

    /*
    |--------------------------------------------------------------------------
    | Student Profile Rules
    |--------------------------------------------------------------------------
    |
    | Constraints applied when creating or editing a student profile.
    |
    */

    'student_id_prefix' => 'EAMU',
    'min_age_years' => 15,
    'max_age_years' => 80,
    'photo_max_kb' => 2048,
    'photo_mimes' => ['jpg', 'jpeg', 'png', 'webp'],

    // A profile is considered complete once these sections are filled in.
    'completeness_checks' => [
        'personal', 'contact', 'address', 'guardian', 'photo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrated Modules
    |--------------------------------------------------------------------------
    |
    | The three subsystems that consume the shared student profile. Each was
    | built as a separate mini project and is surfaced on the profile page.
    |
    */

    'attendance_min_percentage' => 75,

    // A student arriving more than this many minutes after the start is late.
    'late_after_minutes' => 15,

    // How long one QR token stays valid, and how often the kiosk asks for a new
    // one. The refresh is deliberately shorter than the lifetime, so a code is
    // replaced before it expires rather than after.
    'qr_ttl_seconds' => 60,
    'qr_refresh_seconds' => 45,

    'modules' => [
        'attendance' => 'Attendance Management',
        'submissions' => 'Project Submission',
        'complaints' => 'Complaint Management',
    ],

];
