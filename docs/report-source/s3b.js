const L = require('./lib.js');
const fs = require('fs');
const path = require('path');
const { P, H3, Tbl, TableCaption } = L;

// Validation notes keyed "table.column", merged into the generated designs.
const NOTES = {
  'users.email': 'Required, valid email, unique',
  'users.password': 'Required, min 8 characters, bcrypt hash',
  'users.role': 'One of: admin, faculty, student',
  'users.is_active': 'Blocks sign-in when false',
  'departments.code': 'Required, upper case, unique',
  'programs.code': 'Required, upper case, unique',
  'programs.level': 'One of: certificate, diploma, bachelor, master',
  'programs.duration_years': 'Integer 1 to 8',
  'address_types.code': 'Required, unique (PERM, CURR, MAIL)',
  'students.user_id': 'Optional one-to-one link to a sign-in account',
  'students.student_id_no': 'Required, unique, pattern EAMU-YYYY-NNNN',
  'students.gender': 'One of: female, male, other, undisclosed',
  'students.date_of_birth': 'Required; age must be between 15 and 80',
  'students.national_id': 'Optional but unique when supplied',
  'students.email': 'Required, valid email, unique',
  'students.phone': 'Digits, spaces, brackets, plus and hyphen only',
  'students.photo_path': 'Relative path in the public disk',
  'students.intake_year': 'Integer, 2000 to current year + 1',
  'students.status': 'One of: active, on_leave, graduated, withdrawn',
  'students.deleted_at': 'Set when archived; history is retained',
  'student_addresses.is_primary': 'First address entered becomes primary',
  'guardians.relationship': 'One of: mother, father, guardian, sibling, spouse, other',
  'guardians.is_emergency_contact': 'First guardian entered becomes the emergency contact',
  'courses.code': 'Required, upper case, unique across the catalogue',
  'courses.credit_hours': 'Integer 1 to 12',
  'class_sections.lecturer_id': 'The faculty account that teaches this section',
  'class_sections.section_code': 'Unique within a course and term',
  'enrollments.status': 'One of: enrolled, dropped; only enrolled rows form a roster',
  'attendance_sessions.status': 'One of: scheduled, open, closed',
  'attendance_sessions.session_date': 'Unique with the section and start time, so one class cannot meet twice at once',
  'attendance_records.status': 'One of: present, late, absent, excused',
  'attendance_records.student_id': 'Unique with the session, so a student is marked once per meeting',
  'assignments.deadline': 'Required; compared with the server clock to decide lateness',
  'submissions.file_path': 'Generated name on the private disk, never the name the student chose',
  'submissions.original_filename': 'Held only so the download arrives under a recognisable name',
  'submissions.status': 'One of: on_time, late; set by the server, never by the request',
  'submissions.student_id': 'Unique with the assignment, so a resubmission replaces rather than adds',
  'complaints.reference': 'Required, unique, pattern CMP-NNNNN',
  'complaints.category': 'One of: academic, facility, administrative, other',
  'complaints.status': 'One of: pending, in_progress, resolved',
  'complaints.handled_by': 'The registry account that last answered the case',
  'complaints.resolved_at': 'Set on the transition into resolved and cleared when reopened',
};

const KEY = { PRI: 'PK', UNI: 'Unique', MUL: 'FK / Index' };

const ORDER = [
  ['users', 'Authentication and role for every person who can sign in.'],
  ['departments', 'Top-level academic divisions.'],
  ['programs', 'Degree programmes students are admitted into.'],
  ['address_types', 'Lookup for address classification, so the address table stays normalised.'],
  ['students', 'The master student record and the centre of the MIS.'],
  ['student_addresses', 'Addresses held for a student, one row per type.'],
  ['guardians', 'Parent or guardian contacts held for a student.'],
  ['courses', 'The taught-unit catalogue, merged from the attendance module course list and the submission module subject list.'],
  ['class_sections', 'One delivery of a course in a term, taught by one faculty member.'],
  ['enrollments', 'The roster. One row per student per section, and the single source of truth for both attendance and assignments.'],
  ['attendance_sessions', 'One meeting of a class section.'],
  ['attendance_records', 'One student\'s mark for one meeting.'],
  ['assignments', 'A piece of work issued to a class section, with a deadline.'],
  ['submissions', 'One student\'s file against one assignment.'],
  ['complaints', 'A case raised by a student and answered by the registry.'],
];

const raw = fs.readFileSync(path.join(__dirname, 'schema.txt'), 'utf8').trim().split(/\r?\n/);
const byTable = {};
for (const line of raw) {
  const [t, c, type, nullable, key] = line.split('|');
  (byTable[t] ||= []).push({ c, type, nullable, key });
}

const shortType = (t) => t
  .replace('bigint(20) unsigned', 'bigint UN')
  .replace('tinyint(3) unsigned', 'tinyint UN')
  .replace('smallint(5) unsigned', 'smallint UN')
  .replace('tinyint(1)', 'boolean');

const out = [];
ORDER.forEach(([table, purpose], i) => {
  out.push(H3(`3.2.3.${i + 1} ${table}`));
  out.push(P(purpose, { after: 100 }));
  out.push(Tbl(
    ['Column', 'Type', 'Null', 'Key', 'Validation / notes'],
    (byTable[table] || []).map(col => [
      col.c,
      shortType(col.type),
      col.nullable === 'YES' ? 'Yes' : 'No',
      KEY[col.key] || '',
      NOTES[`${table}.${col.c}`] || (col.c === 'id' ? 'Auto-increment' : (col.c.endsWith('_at') ? 'Timestamp' : '')),
    ]),
    [2300, 1800, 700, 1300, 4700],
  ));
  out.push(TableCaption(`Table design: ${table}`));
});

module.exports = out;
