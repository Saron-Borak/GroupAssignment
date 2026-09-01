const D = require('./deck.js');
const { pres, newSlide, heading, speaker, card,
  NAVY, NAVY2, TEAL, GOLD, INK, MUTED, LINE, TINT, WHITE, ATT, SUB, CMP, RED,
  HEAD, BODY, W, Hh, M, CW } = D;

// ===================== 5  INTEGRATION =====================
{
  const s = newSlide(false);
  heading(s, 'What integration actually meant', 'Three foreign keys re-pointed, one catalogue merged, and one difficult conversation.');

  const rows = [
    ['Attendance', 'Referenced its own users table; name and email lived in that database.', 'References students.id. Stores no identity data at all.', ATT],
    ['Project submission', 'A separate subjects table and a second users table; enrolments recorded twice.', 'Assignments hang off the shared class sections; subjects merged into courses.', SUB],
    ['Complaints', 'Referenced a third users table holding only a name and a role.', 'References students.id, so a case resolves to the full profile.', CMP],
  ];

  s.addText('MODULE', { x: M + 0.05, y: 1.9, w: 2.0, h: 0.26, isTextBox: true, margin: 0,
    fontFace: BODY, fontSize: 10, bold: true, charSpacing: 1.2, color: MUTED });
  s.addText('BEFORE', { x: M + 2.3, y: 1.9, w: 4.2, h: 0.26, isTextBox: true, margin: 0,
    fontFace: BODY, fontSize: 10, bold: true, charSpacing: 1.2, color: RED });
  s.addText('AFTER', { x: M + 7.0, y: 1.9, w: 4.2, h: 0.26, isTextBox: true, margin: 0,
    fontFace: BODY, fontSize: 10, bold: true, charSpacing: 1.2, color: TEAL });

  rows.forEach((r, i) => {
    const y = 2.28 + i * 1.16;
    s.addShape(pres.ShapeType.ellipse, { x: M, y: y + 0.06, w: 0.26, h: 0.26, fill: { color: r[3] } });
    s.addText(r[0], { x: M + 0.38, y, w: 1.9, h: 0.6, isTextBox: true, margin: 0,
      fontFace: HEAD, fontSize: 14, bold: true, color: NAVY });
    s.addText(r[1], { x: M + 2.3, y, w: 4.4, h: 0.9, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 11.5, color: MUTED, lineSpacingMultiple: 1.06 });
    s.addText(r[2], { x: M + 7.0, y, w: 4.5, h: 0.9, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 11.5, color: INK, lineSpacingMultiple: 1.06 });
  });

  card(s, { x: M, y: 5.5, w: CW, h: 1.16,
    title: 'The hardest part was not technical',
    body: 'One of us called a taught unit a course, another called it a subject. Agreeing one definition took longer than changing the code.',
    titleSize: 14.5, bodySize: 12 });

  speaker(s, 'MEMBER 2');
  s.addNotes([
    '[4:15-5:45] MEMBER 2 takes over. Ninety seconds.',
    '',
    'Thank you. So what did integration actually involve?',
    'Far less schema surgery than we expected, and far more argument about definitions.',
    'Taking the modules in turn. Attendance used to reference its own users table, and the student name and email lived in that database. Now it references students.id and stores no identity data whatsoever.',
    'Submission was the biggest change. It had a separate subjects table and a second users table, and enrolments were recorded twice - once for attendance, once for assignments. Now assignments hang off the shared class sections, and one enrolment roster serves both modules.',
    'Complaints referenced a third users table that held only a name and a role. Now a complaint resolves to the full profile, so the registry can see who raised it and how they are doing.',
    'Each module also kept its own working service, so it still does its own job: a lecturer marks a register, a student submits a file, the registry answers a case. What changed is where the student comes from.',
    'And the box at the bottom is the honest answer to what was hard. One of us called a taught unit a course, another called it a subject. Same thing, two names, two tables. Agreeing on one definition took a whole meeting; changing the code took an afternoon. That is the real lesson about building an MIS.',
  ].join('\n'));
}

// ===================== 6  READING ACROSS MODULES =====================
{
  const s = newSlide(false);
  heading(s, 'Reading three modules without three hundred queries', 'The obvious implementation works for one student and collapses for a cohort.');

  card(s, { x: M, y: 1.9, w: (CW - 0.5) / 2, h: 2.1,
    kicker: 'THE OBVIOUS WAY', kickerColor: RED,
    title: 'Query each module per student',
    body: 'One profile costs three queries, which is fine. A sixty-student programme report costs one hundred and eighty. The cost grows with enrolment, so it passes a demo and fails in use.',
    bodySize: 12, fill: 'FDF4F3', border: 'EBC7C2' });

  card(s, { x: M + (CW - 0.5) / 2 + 0.5, y: 1.9, w: (CW - 0.5) / 2, h: 2.1,
    kicker: 'WHAT WE DO', kickerColor: TEAL,
    title: 'One grouped query per module',
    body: 'SQL produces every counter in one pass per module using conditional sums, keyed by student. Sixty students and six hundred cost the same three queries.',
    bodySize: 12, fill: 'F2F9F5', border: 'BFDCCB' });

  const figs = [['180', 'queries the naive way', RED], ['3', 'queries our way', TEAL], ['129', 'tests, one asserting exactly this', NAVY]];
  const fw = (CW - 0.64) / 3;
  figs.forEach((f, i) => {
    const x = M + i * (fw + 0.32);
    s.addShape(pres.ShapeType.roundRect, { x, y: 4.3, w: fw, h: 1.4, rectRadius: 0.05,
      fill: { color: TINT }, line: { color: LINE, width: 1 } });
    s.addText(f[0], { x: x + 0.28, y: 4.46, w: fw - 0.56, h: 0.66, isTextBox: true, margin: 0,
      fontFace: HEAD, fontSize: 38, bold: true, color: f[2] });
    s.addText(f[1], { x: x + 0.28, y: 5.14, w: fw - 0.56, h: 0.44, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 11.5, color: MUTED, lineSpacingMultiple: 1.05 });
  });

  s.addText('The test counts the queries, so a future change that reintroduces a loop breaks the build.',
    { x: M, y: 5.9, w: CW, h: 0.4, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 13, italic: true, color: NAVY });

  speaker(s, 'MEMBER 2');
  s.addNotes([
    '[5:45-6:45] MEMBER 2. One minute, then straight into the demo.',
    '',
    'One performance point before I show you the system, because it is where a naive version of this project would fall over.',
    'The profile page shows all three modules together. The obvious way to build that is to query each module for each student. For one profile that is three queries and perfectly fine. For a sixty-student programme report it is one hundred and eighty. The cost grows with enrolment, which means it passes a demonstration with five students and fails with a real cohort.',
    'Instead we run one grouped query per module, using conditional sums keyed by student. Sixty students and six hundred students both cost three queries.',
    'And we have a test that literally counts the queries and fails if there are more than three, so if someone later reintroduces a loop, the build breaks rather than the system quietly getting slower.',
    '',
    'Now let me show you the system.',
  ].join('\n'));
}
