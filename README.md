# Educational Management Information System
### Student Profile Module — East Asia Management University

Group assignment (main project) for *Information System in Business*.

A Laravel 13 + MySQL application that holds the **student record once** and makes it
the single source of identity for three subsystems that previously each kept their
own copy: attendance management, project submission, and complaint management.

---

## The problem this solves

Each group member built a separate application for the individual assignment. All
three worked, and all three stored their own students table:

| Mini project | Held its own | Consequence |
|---|---|---|
| Attendance Management | `users` — id, name, email, role | A phone number change had to be made three times |
| Project Submission | `users` + `subjects` | Enrolments recorded twice |
| Complaint Management | `users` — id, name, role | A complaint could not resolve to a full profile |

No system could answer a question spanning two of them, and required information
(an emergency contact, for instance) was complete nowhere.

**After integration** the three modules store a foreign key and nothing else:

```
attendance_records.student_id  ─┐
submissions.student_id         ─┼─►  students.id
complaints.student_id          ─┘
```

---

## Requirements

| Component | Version |
|---|---|
| PHP | 8.3 or later (built on 8.5) |
| Composer | 2.x |
| MySQL / MariaDB | MariaDB 10.4 via XAMPP |
| Node / npm | **not required** — Bootstrap 5 loads from a CDN |

---

## Setup

**1. Create the database**

```bash
mysql -u root -e "CREATE DATABASE eamu_mis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

On XAMPP for Windows the client is at `C:\xampp\mysql\bin\mysql.exe`.

**2. Install dependencies**

```bash
composer install
```

Copy `.env.example` to `.env` if it does not exist, then confirm `DB_DATABASE=eamu_mis`,
`DB_USERNAME=root` and an empty `DB_PASSWORD`.

**3. Generate a key and link storage (for student photographs)**

```bash
php artisan key:generate
```

```bash
php artisan storage:link
```

**4. Build and seed the database**

```bash
php artisan migrate:fresh --seed
```

**5. Run it**

```bash
php artisan serve
```

`composer setup` runs steps 2–4 in one command.

---

## Demonstration accounts

All seeded accounts use the password **`password`**.

| Role | Email | Sees |
|---|---|---|
| Registry administrator | `registry@eamu.edu` | Every profile, every report |
| Faculty member | `v.meas@eamu.edu` | Only students in the sections they teach |
| Faculty member | `c.nou@eamu.edu` | A different set of sections |
| Student | `theary.heng@student.eamu.edu` | Their own record only |

The seeder prints a current list when it finishes.

**Seeded data:** 4 departments · 6 programmes · 8 courses · 45 student profiles ·
38 addresses · 39 guardians · 180 enrolments · 16 timetable slots · 104 class meetings ·
2,160 attendance records · 24 assignments · 392 submissions · 12 complaints.

Some profiles are deliberately left without a photograph, address or guardian so the
completeness report has real work to show, and roughly one student in eight sits below
the attendance requirement.

Each class also has **one meeting still ahead of it** and **one assignment still open**, so
a demonstration can open a session for QR check-in and submit a file on time, rather than
having to create them first.

---

## What the system does

### Registry (administrator)
- Full profile lifecycle: create, read, update, search, filter, **archive and restore**
- Multiple typed addresses and multiple guardian contacts per student
- Photograph upload with type and size validation
- Departments and programmes
- Issue a student sign-in account; reset passwords; deactivate accounts
- Reports: students by programme, profile completeness — both printable, with CSV download

- **Catalogue:** courses, class sections, weekly timetables and the enrolment roster both modules read
- **Attendance reports:** by class section, and the at-risk list of students below the requirement — printable, with CSV
- **Complaints:** the case queue, plus a by-status / by-category summary over a date range, with CSV
- Withdraw a case raised in error

### Faculty
- Directory of students in their own sections **only**
- Full profile including emergency contact and standing across all three modules
- **My classes:** timetable, roster, register history and assignments for one class on a single screen

**Attendance module**
- Create, edit and delete a class meeting; generate a whole term from the weekly timetable
- Mark the register present / late / absent / excused, by hand
- **Open a session for self check-in:** a projector kiosk with a rotating QR code, a six-character
  fallback code, a live countdown and a running tally
- **Close a session:** everyone on the roster who never checked in is marked absent in one write
- Per-class attendance report with a date range, printable, with CSV

**Project submission module**
- Issue, edit and withdraw an assignment
- See who submitted, who was late and who is missing; download any submission

### Student
- Their own profile and module standing
- **My classes:** what you are enrolled in, with your attendance in each
- **Browse the catalogue** and enrol yourself, or leave a class that holds nothing about you yet

**Attendance module**
- **Check in** by scanning the QR code on the projector, or by typing the six-character code
- Your percentage per class, and the session-by-session history behind it
- Warning when attendance is below the requirement

**Project submission module**
- Submit and replace a file, with on-time or late decided by the **server clock**

**Complaint module**
- Raise a case, get a `CMP-00001` reference, read the registry's reply
- Change your own password

---

## Architecture

```
Routes → Middleware (role, active) → Controller → Form Request (validation)
                                          ↓
                                   Policy (ownership)
                                          ↓
   StudentProfile · Attendance · Submission · Complaint  |  StudentInsight
              (each owns its module's writes)             |  (reads all 3)
                                          ↓
                              Eloquent models → MySQL (15 tables)
```

Six service classes carry the rules — four own writes, two own reads:

- **`StudentProfileService`** — every profile write. A profile spans three tables plus an
  optional account, so creation is a transaction, not an insert.
- **`AttendanceService`** — every register write, whichever of the three doors it came
  through: the lecturer marking by hand, a QR scan, or a typed code. Marks are filtered
  against the active roster, so a tampered form cannot write against another student; the
  write is an upsert, so correcting a register updates rows instead of adding a second set;
  and closing a session marks every no-show absent in one statement.
- **`AttendanceReportService`** — every attendance read that spans more than one student.
  A roster summary is **one grouped query** with conditional sums, whatever the class size.
- **`SubmissionService`** — every upload. Files go to the **private** disk under a generated
  name; on-time or late is decided by comparing the deadline with the **server clock**.
- **`ComplaintService`** — every case. Issues the `CMP-#####` reference and owns the
  transition into and out of *resolved*, clearing the resolution date when a case reopens.
- **`StudentInsightService`** — every cross-module read. `forCohort()` summarises any number
  of students in **three queries** (one per module) rather than three per student.

---

## Design decisions worth defending

| Decision | Why |
|---|---|
| Addresses and guardians are separate tables | A student may hold several of each; columns would form a repeating group and cap the number |
| `address_types` is a lookup table | Keeps `student_addresses` in 3NF; renaming a type is one row |
| Archive (soft delete), never hard delete | Destroying a profile would orphan the attendance, submission and complaint history the university must retain |
| Attendance percentage is **derived, never cached** | A cached value drifts the moment any module writes without recalculating, and a stale figure could bar a student from an exam |
| `UNIQUE (attendance_session_id, student_id)` | Makes a duplicate attendance record impossible rather than merely unlikely |
| Access checked **twice** | Middleware proves the role; a policy proves ownership of the individual record |
| Submitted files on the **private** disk | A file under `public/` is reachable by anyone who guesses the URL; these are released only by a route that has checked who is asking |
| Lateness decided by the **server clock** | A deadline the browser can influence is not a deadline; the same rule decides a late arrival at a class |
| The QR token **rotates** every minute | A photograph of the projector shared outside the room stops working, so a student cannot check a friend in |
| A six-character code beside the QR | The feature stays demonstrable without a second device, and works when a camera will not focus |
| Closing a session marks the no-shows absent | A session left open reads as though nobody was absent, quietly inflating every percentage drawn from it |
| Only **closed** sessions count toward a percentage | A meeting still in progress must not drag a student's number down before anyone has been marked |
| A student may leave a class only while it holds nothing about them | Otherwise un-enrolling would be a way to erase your own register |

---

## Tests

```bash
php artisan test
```

129 tests / 390 assertions against in-memory SQLite, with Eloquent lazy loading disabled
so an accidental N+1 fails the build.

| Suite | Covers |
|---|---|
| `StudentProfileTest` | CRUD, duplicates, age and phone rules, normalisation, archive/restore |
| `PhotoUploadTest` | Valid upload, wrong type, oversize, replacement cleanup, removal |
| `ModuleIntegrationTest` | Attendance maths, excused handling, submission and complaint counts, **cohort query count**, archive retains history |
| `ModuleWorkflowTest` | Marking and re-marking a register, roster filtering, session clashes; submitting, late detection, replacement, executable rejection; filing a case, references, registry response, reopening |
| `AccessControlTest` | Sign-in, role routing, deactivated accounts, cross-portal refusals, ownership policies |
| `PagesRenderTest` | Every screen in all three portals, including the module screens; search filtering; CSV export |
| `CheckInTest` | QR scan and typed code; expired token, closed session, outsider, duplicate; opening, rotation, and closing marking the no-shows absent |
| `CatalogueTest` | Course and section CRUD, duplicate codes, registry and student enrolment, dropping vs deleting, timetable slots, session generation |
| `AttendanceReportTest` | Late counts as attended, excused leaves the denominator, open sessions excluded, at-risk ordering, one query per roster |
| `EnumBehaviourTest` | The rules the modules share: what counts as attendance, which cases are open, where each role lands |

---

## Deliverables

| File | Contents |
|---|---|
| `docs/EAMU-MIS-Group-Report.docx` | Group report — SRS, ERD, table designs, implementation, testing, appendices. Cambria 12pt, 0.5in margins, 1.15 spacing, APA |
| `docs/EAMU-MIS-Group-Presentation.pptx` | 12-slide, 15-minute presentation with speaker notes split across all three members |
| `docs/report-source/`, `docs/deck-source/` | Generators for both documents, so they can be rebuilt if the system changes |

Both documents contain clearly marked placeholders for group member names and screenshots.
