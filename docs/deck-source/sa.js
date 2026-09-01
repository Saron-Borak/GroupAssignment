const D = require('./deck.js');
const { pres, newSlide, heading, speaker, card,
  NAVY, NAVY2, TEAL, GOLD, INK, MUTED, LINE, TINT, WHITE, ATT, SUB, CMP, RED,
  HEAD, BODY, W, Hh, M, CW } = D;

// ===================== 1  TITLE =====================
{
  const s = newSlide(true);
  s.addText('EAST ASIA MANAGEMENT UNIVERSITY', { x: M, y: 1.35, w: CW, h: 0.3, isTextBox: true, margin: 0,
    fontFace: BODY, fontSize: 12.5, bold: true, charSpacing: 2.4, color: TEAL });
  s.addText('Educational MIS', { x: M, y: 1.8, w: 8.6, h: 0.82, isTextBox: true, margin: 0,
    fontFace: HEAD, fontSize: 48, bold: true, color: WHITE });
  s.addText('Student Profile Module', { x: M, y: 2.58, w: 8.6, h: 0.82, isTextBox: true, margin: 0,
    fontFace: HEAD, fontSize: 48, bold: true, color: WHITE });
  s.addText('One student record, serving attendance, project submission and complaint management.',
    { x: M, y: 3.6, w: 8.0, h: 0.5, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 15, color: 'B9C6DB' });

  [['Attendance', ATT], ['Submissions', SUB], ['Complaints', CMP]].forEach((m, i) => {
    s.addShape(pres.ShapeType.ellipse, { x: M + i * 2.3, y: 4.3, w: 0.18, h: 0.18, fill: { color: m[1] } });
    s.addText(m[0], { x: M + 0.28 + i * 2.3, y: 4.24, w: 2.0, h: 0.3, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 11.5, color: '9DAAC2' });
  });

  s.addText([
    { text: 'Group Assignment - Main Project', options: { bold: true, breakLine: true } },
    { text: '[ Member 1 ]   ·   [ Member 2 ]   ·   [ Member 3 ]', options: { breakLine: true } },
    { text: 'Information System in Business  ·  [ Date ]', options: {} },
  ], { x: M, y: 5.0, w: 7.5, h: 1.1, isTextBox: true, margin: 0,
       fontFace: BODY, fontSize: 12.5, color: '9DAAC2', lineSpacingMultiple: 1.25 });

  const px = 9.1, pw = 3.55;
  s.addShape(pres.ShapeType.roundRect, { x: px, y: 1.8, w: pw, h: 3.5, rectRadius: 0.06,
    fill: { color: NAVY2 }, line: { color: '2C4574', width: 1 } });
  s.addText('THE SYSTEM', { x: px + 0.3, y: 2.05, w: pw - 0.6, h: 0.26, isTextBox: true, margin: 0,
    fontFace: BODY, fontSize: 9.5, bold: true, charSpacing: 1.6, color: TEAL });
  [['16', 'database tables'], ['3', 'systems integrated'], ['129', 'automated tests'], ['1', 'student record']]
    .forEach((r, i) => {
      const y = 2.45 + i * 0.68;
      s.addText(r[0], { x: px + 0.3, y, w: 1.1, h: 0.42, isTextBox: true, margin: 0,
        fontFace: HEAD, fontSize: 23, bold: true, color: WHITE });
      s.addText(r[1], { x: px + 1.5, y: y + 0.1, w: pw - 1.8, h: 0.38, isTextBox: true, margin: 0,
        fontFace: BODY, fontSize: 11, color: '9DAAC2' });
    });

  speaker(s, 'MEMBER 1', true);
  s.addNotes([
    '[0:00-0:30] MEMBER 1 opens.',
    '',
    'Good morning. Our group project is the Student Profile module of an Educational Management Information System.',
    'The one-line version: we had three separate systems that each stored their own copy of a student, and we replaced them with one shared record that all three now read from.',
    'I will cover the problem and the database design, [Member 2] will show the integration and demonstrate the system, and [Member 3] will cover testing and what we learned.',
  ].join('\n'));
}

// ===================== 2  THE PROBLEM =====================
{
  const s = newSlide(false);
  heading(s, 'Three systems, three students', 'Each of us built a working application. Together they held the same person three times.');

  const before = [
    ['Attendance system', 'users: id, name, email, role', ATT],
    ['Submission system', 'users: id, name, email, role', SUB],
    ['Complaint system', 'users: id, name, role', CMP],
  ];
  before.forEach((b, i) => {
    const x = M + i * ((CW - 0.6) / 3 + 0.3), w = (CW - 0.6) / 3;
    s.addShape(pres.ShapeType.roundRect, { x, y: 1.9, w, h: 1.5, rectRadius: 0.05,
      fill: { color: 'FDF4F3' }, line: { color: 'EBC7C2', width: 1 } });
    s.addShape(pres.ShapeType.ellipse, { x: x + 0.26, y: 2.14, w: 0.28, h: 0.28, fill: { color: b[2] } });
    s.addText(b[0], { x: x + 0.66, y: 2.12, w: w - 0.92, h: 0.3, isTextBox: true, margin: 0,
      fontFace: HEAD, fontSize: 14.5, bold: true, color: NAVY });
    s.addText(b[1], { x: x + 0.26, y: 2.56, w: w - 0.52, h: 0.34, isTextBox: true, margin: 0,
      fontFace: 'Consolas', fontSize: 11, color: MUTED });
    s.addText('own database', { x: x + 0.26, y: 2.96, w: w - 0.52, h: 0.3, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 10.5, italic: true, color: 'B0554E' });
  });

  const problems = [
    ['Stored three times', 'A change of phone number must be made in three places, and is made in one.'],
    ['No question can span two', 'Who is below the attendance rule and has an open complaint? Nothing can answer it.'],
    ['No authoritative record', 'When two systems disagree, nothing decides which is right.'],
    ['Complete nowhere', 'Nobody stores the emergency contact, because no system owns it.'],
  ];
  const cw = (CW - 0.45) / 2;
  problems.forEach((p, i) => {
    const x = M + (i % 2) * (cw + 0.45);
    const y = 3.72 + Math.floor(i / 2) * 1.28;
    s.addText(p[0], { x, y, w: cw, h: 0.32, isTextBox: true, margin: 0,
      fontFace: HEAD, fontSize: 16, bold: true, color: RED });
    s.addText(p[1], { x, y: y + 0.34, w: cw, h: 0.6, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 12.5, color: INK, lineSpacingMultiple: 1.08 });
  });

  speaker(s, 'MEMBER 1');
  s.addNotes([
    '[0:30-2:00] MEMBER 1. Ninety seconds. This is the whole motivation - take your time.',
    '',
    'For the individual assignment the three of us built three different systems: attendance, project submission, and complaints. All three worked.',
    'But look at the top row. Each one had its own users table, in its own database, holding its own copy of the student. The same person existed three times.',
    'That produces four problems. A phone number change has to be made in three places, so in practice it gets made in one, and the other two go stale.',
    'Second, and this is the one that matters for an MIS: no system can answer a question that spans two of them. "Which students are below the attendance requirement and also have an unresolved complaint?" is a perfectly reasonable question for a registry to ask, and it was unanswerable, because no single database held both facts.',
    'Third, when two systems disagreed there was nothing to say which was correct.',
    'And fourth, required information was complete nowhere. None of us stored an emergency contact, because none of our systems considered it their job.',
  ].join('\n'));
}
