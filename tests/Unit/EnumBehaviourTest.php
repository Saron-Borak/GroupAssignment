<?php

namespace Tests\Unit;

use App\Enums\AttendanceStatus;
use App\Enums\ComplaintStatus;
use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

/**
 * The enums carry the rules the three modules agree on — what counts as
 * attendance, which cases are still open, where each role lands after login.
 * These need no database, so they are the one genuine unit suite.
 */
class EnumBehaviourTest extends TestCase
{
    public function test_late_still_counts_as_attended(): void
    {
        $this->assertTrue(AttendanceStatus::Present->countsAsAttended());
        $this->assertTrue(AttendanceStatus::Late->countsAsAttended());
        $this->assertFalse(AttendanceStatus::Absent->countsAsAttended());
        $this->assertFalse(AttendanceStatus::Excused->countsAsAttended());
    }

    public function test_every_attendance_status_has_a_distinct_badge(): void
    {
        $badges = array_map(fn (AttendanceStatus $s) => $s->badgeClass(), AttendanceStatus::cases());

        $this->assertCount(count(AttendanceStatus::cases()), array_unique($badges));
    }

    public function test_only_resolved_cases_are_closed(): void
    {
        $open = array_filter(ComplaintStatus::cases(), fn (ComplaintStatus $s) => $s->isOpen());

        $this->assertSame(
            [ComplaintStatus::Pending, ComplaintStatus::InProgress],
            array_values($open),
        );
    }

    public function test_each_role_has_its_own_landing_route(): void
    {
        $routes = array_map(fn (UserRole $r) => $r->homeRoute(), UserRole::cases());

        $this->assertCount(count(UserRole::cases()), array_unique($routes));
    }

    public function test_every_enum_case_labels_itself(): void
    {
        foreach ([...AttendanceStatus::cases(), ...ComplaintStatus::cases(), ...SubmissionStatus::cases(), ...UserRole::cases()] as $case) {
            $this->assertNotSame('', $case->label(), $case::class.'::'.$case->name.' has no label');
        }
    }
}
