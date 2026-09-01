const D = require('./deck.js');
const { pres, newSlide, heading, speaker, card,
  NAVY, NAVY2, TEAL, GOLD, INK, MUTED, LINE, TINT, WHITE, ATT, SUB, CMP, RED,
  HEAD, BODY, W, Hh, M, CW } = D;

// ===================== 3  WHAT WE BUILT =====================
{
  const s = newSlide(false);
  heading(s, 'One record at the centre', 'The Student Profile module holds the student once. Everything else resolves through it.');

  s.addShape(pres.ShapeType.roundRect, { x: 4.85, y: 2.5, w: 3.6, h: 1.5, rectRadius: 0.06,
    fill: { color: NAVY }, line: { color: NAVY, width: 0 } });
  s.addText('STUDENT PROFILE', { x: 4.95, y: 2.78, w: 3.4, h: 0.28, isTextBox: true, margin: 0,
    align: 'center', fontFace: BODY, fontSize: 10, bold: true, charSpacing: 1.6, color: TEAL });
  s.addText('One record', { x: 4.95, y: 3.08, w: 3.4, h: 0.42, isTextBox: true, margin: 0,
    align: 'center', fontFace: HEAD, fontSize: 22, bold: true, color: WHITE });
  s.addText('identity · contact · address · guardian', { x: 4.95, y: 3.52, w: 3.4, h: 0.3, isTextBox: true, margin: 0,
    align: 'center', fontFace: BODY, fontSize: 10.5, color: '9DAAC2' });

  const mods = [
    ['Attendance', 'attendance_records.student_id', ATT, 0.9],
    ['Project submission', 'submissions.student_id', SUB, 5.0],
    ['Complaints', 'complaints.student_id', CMP, 9.1],
  ];
  mods.forEach((m) => {
    s.addShape(pres.ShapeType.roundRect, { x: m[3], y: 4.9, w: 3.35, h: 1.15, rectRadius: 0.05,
      fill: { color: TINT }, line: { color: m[2], width: 1.5 } });
    s.addText(m[0], { x: m[3] + 0.22, y: 5.06, w: 2.9, h: 0.3, isTextBox: true, margin: 0,
      fontFace: HEAD, fontSize: 14.5, bold: true, color: NAVY });
    s.addText(m[1], { x: m[3] + 0.22, y: 5.42, w: 3.0, h: 0.34, isTextBox: true, margin: 0,
      fontFace: 'Consolas', fontSize: 10, color: MUTED });
    s.addShape(pres.ShapeType.line, { x: m[3] + 1.67, y: 4.0, w: 6.65 - (m[3] + 1.67), h: 0.9,
      line: { color: 'AEB8C8', width: 1.25, beginArrowType: 'triangle' } });
  });

  s.addText('No module stores a name, an email or a phone number. They store a foreign key.',
    { x: M, y: 6.3, w: CW, h: 0.4, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 13.5, italic: true, bold: true, color: NAVY });

  speaker(s, 'MEMBER 1');
  s.addNotes([
    '[2:00-3:00] MEMBER 1. One minute.',
    '',
    'So this is what we built. The Student Profile module in the middle holds the student once: identity, contact details, addresses and guardians.',
    'The three modules underneath no longer hold any of that. Look at what each one stores now - a foreign key. attendance_records has a student_id. submissions has a student_id. complaints has a student_id. Not one of them stores a name, an email or a phone number any more.',
    'That is the whole design in one picture, and it is what makes it a management information system rather than three applications in a folder.',
    'Each module still does its own work - registers are marked, files are submitted, cases are answered - but the student it does that work for comes from one place.',
    'The line at the bottom is the test of whether integration actually happened: if a module still stores a name, it has not been integrated.',
  ].join('\n'));
}

// ===================== 4  DATABASE DESIGN =====================
{
  const s = newSlide(false);
  heading(s, 'Fifteen tables, third normal form', 'Two design decisions we can defend, and one we deliberately rejected.');

  card(s, { x: M, y: 1.86, w: (CW - 0.4) / 2, h: 2.0,
    kicker: 'DECISION 1', kickerColor: TEAL,
    title: 'Addresses are a table, not columns',
    body: 'A student may hold a permanent, term-time and mailing address. Columns address_1 to address_3 would be a repeating group and would cap the number. The type is a lookup, so changing the wording is one row.',
    bodySize: 12 });

  card(s, { x: M + (CW - 0.4) / 2 + 0.4, y: 1.86, w: (CW - 0.4) / 2, h: 2.0,
    kicker: 'DECISION 2', kickerColor: TEAL,
    title: 'Archive, never delete',
    body: 'Deleting a profile would destroy the attendance, submission and complaint history that references it. A soft delete hides the record and keeps the history the university must retain.',
    bodySize: 12 });

  card(s, { x: M, y: 4.02, w: CW, h: 1.66,
    kicker: 'REJECTED', kickerColor: RED,
    title: 'Caching the attendance percentage on the profile',
    body: 'It would have made reporting trivial. We rejected it because the value drifts the moment any subsystem writes a record without recalculating, and a stale percentage here could bar a student from an examination. The figure is always derived.',
    bodySize: 12, fill: 'FDF4F3', border: 'EBC7C2' });

  s.addText('Correctness lives in the schema: UNIQUE (session, student) makes a duplicate attendance record impossible, not merely unlikely.',
    { x: M, y: 5.84, w: CW, h: 0.42, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 13, italic: true, color: MUTED });

  speaker(s, 'MEMBER 1');
  s.addNotes([
    '[3:00-4:15] MEMBER 1. Seventy-five seconds, then hand over.',
    '',
    'Fifteen tables in third normal form. Three decisions worth defending.',
    'First, addresses. A student can have a permanent address, a term-time address and a mailing address. We could have put three sets of columns on the student, but that is a repeating group and it caps the number arbitrarily. So addresses are their own table, and the type is a lookup - which means if the registry wants to rename "Permanent address", that is one row, not thousands.',
    'Second, we archive rather than delete. If you delete a profile you destroy the attendance and complaint history that points at it, and the university is required to keep that. A soft delete hides the record and preserves everything.',
    'Third, the one we rejected. We could have stored the attendance percentage on the profile and made reporting trivial. We did not, because that number goes stale the moment any subsystem writes a record without recalculating it - and a wrong attendance percentage here can bar a student from an exam. So we always derive it, and [Member 2] will explain how we kept that cheap.',
    'The line at the bottom is the principle behind all three: put correctness in the schema where you can.',
    '',
    'HAND OVER: "[Member 2] will now show how the three systems were actually integrated."',
  ].join('\n'));
}
