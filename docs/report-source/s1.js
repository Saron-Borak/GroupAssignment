const L = require('./lib.js');
const { P, Rich, H1, H2, H3, Bullet, Num, Tbl, TableCaption } = L;

module.exports = [
  H1('1. Introduction'),

  H2('1.1 Background'),
  P('A management information system turns scattered operational data into information that supports a decision. In education that decision is usually about a student, and depends on knowing who the student is. Yet the student record is the piece most often duplicated across systems.'),
  P('This project began from an instance of that problem inside our group. Each member had built a separate application for the individual assignment: attendance, project submission and complaint management. All three worked, and all three held their own copy of the student, with contact details that could and did diverge.'),

  H2('1.2 Problem Statement'),
  P('When each subsystem owns its own student record, four problems follow.'),
  Bullet('The same person is stored several times, so a change of telephone number must be made in every system and in practice is made in one.'),
  Bullet('No system can answer a question spanning two of them, because no single database holds both facts.'),
  Bullet('There is no authoritative record. When two systems disagree about a student\u2019s programme or email address, nothing determines which is correct.'),
  Bullet('Required information is complete nowhere: nobody records the emergency contact, because no system considers it their responsibility.'),
  P('The Student Profile module addresses this directly: it holds the record once, in third normal form, and every other module resolves a student through it.'),

  H2('1.3 Aim and Objectives'),
  P('The aim is to build the Student Profile module of an educational MIS and demonstrate that it can serve as the single source of student identity for three previously independent subsystems.'),
  Num('Document the requirements as a formal SRS, following IEEE Std 830 (IEEE, 1998).'),
  Num('Design a normalised schema storing the student record exactly once.'),
  Num('Implement CRUD, search, filtering, validation and photograph upload.'),
  Num('Integrate the three subsystems so each resolves a student through the shared profile.'),
  Num('Provide programme-level and profile-completeness reporting.'),
  Num('Verify the result by automated testing of validation, authorisation and cross-module correctness.'),

  H2('1.4 Scope'),
  H3('1.4.1 In scope'),
  Bullet('Full profile lifecycle: create, read, update, search, filter, archive and restore.'),
  Bullet('Supporting reference data: departments, programmes and address types.'),
  Bullet('Multiple typed addresses and multiple guardian contacts per student.'),
  Bullet('Photograph upload with type and size validation.'),
  Bullet('Role-based access with per-record ownership checks.'),
  Bullet('Live integration with all three subsystems.'),
  Bullet('Reporting by programme and completeness, with CSV and print output.'),

  H3('1.4.2 Out of scope'),
  P('The following were excluded deliberately and are revisited in Section 8.'),
  Bullet('The full interfaces of the three subsystems. They are integrated here at the data level; this project delivers the profile module, not a rewrite of all three.'),
  Bullet('Grade and assessment management.'),
  Bullet('Outbound email or SMS. Password resets are performed in person by the registry.'),
  Bullet('Deployment to a public host; deployment is treated conceptually, as the brief specifies.'),

  H2('1.5 Team Organisation'),
  P('Work was divided along the boundary each member already understood: whoever built a subsystem integrated it.'),
  Tbl(['Member', 'Individual mini project', 'Responsibility in this project'], [
    ['[ Member 1 ]', 'Student Attendance Management System', 'Student Profile data model and CRUD; validation rules; attendance integration; report writing'],
    ['[ Member 2 ]', 'Student Project Submission System', 'Search, filtering and reporting; submission integration; CSV export; testing'],
    ['[ Member 3 ]', 'Student Complaint Management System', 'Authentication and role-based access; complaint integration; user interface and presentation'],
  ], [2400, 3600, 4800]),
  TableCaption('Division of responsibilities'),
  P('Decisions affecting the shared schema were taken jointly, since a change to the students table affected all three integrations. Appendix C records those discussions.'),

  H2('1.6 Structure of this Report'),
  P('Section 2 covers requirements, Section 3 design, Section 4 implementation, Section 5 testing and Section 6 a walkthrough. Sections 7 to 9 cover lessons, future scope and conclusions.'),
];
