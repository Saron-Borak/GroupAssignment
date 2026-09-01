const D = require('./deck.js');
const { pres, newSlide, heading, speaker, card,
  NAVY, NAVY2, TEAL, GOLD, INK, MUTED, LINE, TINT, WHITE, ATT, SUB, CMP, RED,
  HEAD, BODY, W, Hh, M, CW } = D;

// ===================== 7  LIVE DEMONSTRATION =====================
{
  const s = newSlide(true);
  s.addText('LIVE DEMONSTRATION', { x: M, y: 1.4, w: CW, h: 0.34, isTextBox: true, margin: 0,
    fontFace: BODY, fontSize: 12.5, bold: true, charSpacing: 2.6, color: TEAL });
  s.addText('The system, running', { x: M, y: 1.86, w: 7.6, h: 0.78, isTextBox: true, margin: 0,
    fontFace: HEAD, fontSize: 38, bold: true, color: WHITE });
  s.addText('45 profiles, 8 class sections, 104 meetings, 2,160 marks, 392 submissions and 12 cases.',
    { x: M, y: 2.72, w: 7.6, h: 0.5, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 14, color: 'B9C6DB' });

  const steps = [
    ['Registry dashboard', 'Three module signals on one screen', TEAL],
    ['Student profile', 'All three modules on one record', GOLD],
    ['QR check-in', 'Students mark themselves, live', TEAL],
    ['Close the session', 'No-shows marked absent in one write', GOLD],
    ['Submit and collect', 'Student uploads, lecturer downloads', TEAL],
    ['Raise and answer a case', 'Student to registry and back', GOLD],
    ['At-risk report', 'Who is below the requirement', TEAL],
    ['Access control', 'Crossing a role boundary', RED],
  ];
  steps.forEach((st, i) => {
    const x = M + (i % 2) * (CW / 2 + 0.16);
    const y = 3.52 + Math.floor(i / 2) * 0.86;
    s.addShape(pres.ShapeType.ellipse, { x, y: y + 0.06, w: 0.3, h: 0.3, fill: { color: st[2] } });
    s.addText(String(i + 1), { x, y: y + 0.095, w: 0.3, h: 0.26, isTextBox: true, margin: 0,
      align: 'center', fontFace: HEAD, fontSize: 11, bold: true, color: st[2] === GOLD ? NAVY : WHITE });
    s.addText(st[0], { x: x + 0.46, y, w: 2.6, h: 0.3, isTextBox: true, margin: 0,
      fontFace: HEAD, fontSize: 14, bold: true, color: WHITE });
    s.addText(st[1], { x: x + 0.46, y: y + 0.29, w: CW / 2 - 0.62, h: 0.42, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 11.5, color: '9DAAC2', lineSpacingMultiple: 1.05 });
  });

  speaker(s, 'MEMBER 2 DRIVES  ·  MEMBER 3 NARRATES', true);
  s.addNotes([
    '[6:45-10:45] LIVE DEMONSTRATION. Four minutes.',
    'MEMBER 2 drives the keyboard; MEMBER 3 narrates, so both are visibly involved.',
    '',
    'BEFORE YOU START:',
    '  - Server already running: php artisan serve --port=8001',
    '  - Signed in as registry@eamu.edu / password',
    '  - A second browser or private window at the login page',
    '  - A small PDF on the desktop, ready to upload in step 5',
    '  - A SECOND DEVICE or private window signed in as a student, for the check-in',
    '  - If the data has been altered: php artisan migrate:fresh --seed',
    '',
    'RUNNING ORDER:',
    '1. REGISTRY DASHBOARD (25s). 45 active students, 6 programmes. Point at the three module signals: students below the attendance rule, submissions this week, open complaints. Say: three different systems, one screen. That is the thing none of us could do alone.',
    '2. STUDENT PROFILE (40s). Open any student. Show the identity band and completeness, then the three coloured cards. Switch the activity tabs between Attendance and Submissions to show it is live data, not a summary table.',
    '3. QR CHECK-IN (55s). THE SET PIECE - rehearse this one. As the lecturer, open tomorrow\u2019s session; the kiosk appears with the QR code, the six-character code and a countdown. On the second device, sign in as a student and type the code: "Checked in. You are marked present." The kiosk tally moves without the page reloading. Say: the code is replaced about every minute, so a photograph sent to somebody outside the room is already dead.',
    '4. CLOSE THE SESSION (25s). Press Close session. Read the message aloud: twenty-one students who never checked in were marked absent. Say: that is one write, and it is why a percentage means something - a session left open would look like nobody was absent.',
    '5. SUBMIT AND COLLECT (30s). In the student window, My assignments: upload the PDF and show it recorded as on time. Back in the faculty window, open the assignment and show the submission appear with the student number in the filename. Say: the file is on a private disk, so it is not reachable by guessing a URL.',
    '6. RAISE AND ANSWER A CASE (30s). As the student, file a complaint and read out the reference. As the registry, open it, note the student profile on the right, write a reply, set it to Resolved. Back as the student, show the reply on their own copy.',
    '7. AT-RISK REPORT (20s). Open the at-risk list. Twenty-one enrolments below seventy-five per cent, worst first. Say: one grouped query, so this costs the same for forty-five students or four thousand.',
    '8. ACCESS CONTROL (15s). As the faculty member, paste the URL of a student they do not teach. Access denied. Say: middleware alone would have allowed that; the ownership policy is what stops it.',
    '',
    'IF THE DEMO FAILS: do not debug on stage. Say the report contains screenshots of every screen, move to the testing slide, and recover afterwards.',
  ].join('\n'));
}

// ===================== 8  TESTING =====================
{
  const s = newSlide(false);
  heading(s, 'Tested at four levels', '129 automated tests running through the real routing stack, plus a manual acceptance pass.');

  const figs = [['129', 'tests', NAVY], ['390', 'assertions', NAVY], ['3.6s', 'to run', TEAL], ['80', 'documented cases', GOLD]];
  const fw = (CW - 0.96) / 4;
  figs.forEach((f, i) => {
    const x = M + i * (fw + 0.32);
    s.addShape(pres.ShapeType.roundRect, { x, y: 1.86, w: fw, h: 1.2, rectRadius: 0.05,
      fill: { color: TINT }, line: { color: LINE, width: 1 } });
    s.addText(f[0], { x: x + 0.24, y: 1.98, w: fw - 0.48, h: 0.6, isTextBox: true, margin: 0,
      fontFace: HEAD, fontSize: 34, bold: true, color: f[2] });
    s.addText(f[1], { x: x + 0.24, y: 2.6, w: fw - 0.48, h: 0.34, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 11.5, color: MUTED });
  });

  const suites = [
    ['Unit', 'The rules the modules share, held on the enumerations'],
    ['Integration', 'That all three modules resolve one student and agree'],
    ['System', 'Every screen, and every module write path, through the real routes'],
    ['Acceptance', 'The full demonstration path, walked manually'],
  ];
  suites.forEach((r, i) => {
    const y = 3.32 + i * 0.62;
    s.addShape(pres.ShapeType.roundRect, { x: M, y, w: 7.3, h: 0.52, rectRadius: 0.04,
      fill: { color: i % 2 ? WHITE : TINT }, line: { color: LINE, width: 1 } });
    s.addText(r[0], { x: M + 0.22, y: y + 0.12, w: 1.5, h: 0.3, isTextBox: true, margin: 0,
      fontFace: HEAD, fontSize: 13, bold: true, color: NAVY });
    s.addText(r[1], { x: M + 1.85, y: y + 0.13, w: 5.3, h: 0.3, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 11.5, color: MUTED });
  });

  card(s, { x: M + 7.7, y: 3.32, w: CW - 7.7, h: 2.44,
    kicker: 'A USEFUL SETTING', kickerColor: TEAL,
    title: 'Lazy loading off in tests',
    body: 'Any relationship the code forgot to load in advance throws, instead of quietly firing an extra query per row. An N+1 bug fails the build rather than shipping.',
    titleSize: 14.5, bodySize: 11.5 });

  speaker(s, 'MEMBER 3');
  s.addNotes([
    '[10:45-12:00] MEMBER 3 takes over. Seventy-five seconds.',
    '',
    'Thank you. We tested at the four levels the brief asks for.',
    'A hundred and twenty-nine automated tests, three hundred and ninety assertions, running in under four seconds - which matters, because a slow suite is one you stop running.',
    'The important choice is that the system tests go through the real routing stack rather than calling methods directly. A single assertion therefore exercises routing, middleware, the ownership policies, validation, the service layer and the database together.',
    'The integration tests are the ones specific to this project: they create records in all three modules for one student, then check the profile reports figures consistent with each. Beyond that, sixty-six tests cover the module write paths - marking a register, checking in by QR, submitting a file, answering a complaint - including the cases we most wanted to be sure of: a mark forged for a student outside the roster, an expired check-in code, a resubmission after the deadline, and a reopened case.',
    'And on the right, we disable lazy loading during testing. If the code forgets to eager-load a relationship it throws, instead of quietly firing an extra query per row. So an N-plus-one performance bug fails the build rather than shipping silently.',
    'The report documents eighty test cases individually, each traced to a numbered requirement.',
  ].join('\n'));
}
