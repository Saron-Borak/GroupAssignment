const D = require('./deck.js');
const { pres, newSlide, heading, speaker, card,
  NAVY, NAVY2, TEAL, GOLD, INK, MUTED, LINE, TINT, WHITE, ATT, SUB, CMP, RED,
  HEAD, BODY, W, Hh, M, CW } = D;

// ===================== 9  SECURITY =====================
{
  const s = newSlide(false);
  heading(s, 'Security and access control', 'Two layers, because the two checks answer different questions.');

  card(s, { x: M, y: 1.88, w: (CW - 0.5) / 2, h: 1.95,
    kicker: 'LAYER 1  -  MIDDLEWARE', kickerColor: TEAL,
    title: 'Is this the right kind of user?',
    body: 'Role middleware gates each portal. Necessary, but it confirms only that the visitor is a faculty member, not which faculty member.',
    bodySize: 12 });

  card(s, { x: M + (CW - 0.5) / 2 + 0.5, y: 1.88, w: (CW - 0.5) / 2, h: 1.95,
    kicker: 'LAYER 2  -  POLICY', kickerColor: GOLD,
    title: 'Is this their record?',
    body: 'A policy checks that the student is enrolled in a section this lecturer teaches. Without it, any lecturer could open any profile by editing the URL.',
    bodySize: 12 });

  const threats = [
    ['SQL injection', 'Every query binds parameters through the ORM. No input is concatenated into SQL.'],
    ['Cross-site scripting', 'Blade escapes all output by default; nothing is rendered unescaped.'],
    ['Cross-site request forgery', 'Every state-changing form carries a verified token.'],
    ['Malicious upload', 'Photographs restricted by MIME type and size, renamed, stored outside the source tree.'],
    ['Brute force', 'Five attempts per email and IP address, then a lockout.'],
  ];
  threats.forEach((t, i) => {
    const y = 4.1 + i * 0.5;
    s.addText(t[0], { x: M, y, w: 3.0, h: 0.3, isTextBox: true, margin: 0,
      fontFace: HEAD, fontSize: 12.5, bold: true, color: NAVY });
    s.addText(t[1], { x: M + 3.1, y: y + 0.01, w: CW - 3.1, h: 0.3, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 11.5, color: MUTED });
  });

  speaker(s, 'MEMBER 3');
  s.addNotes([
    '[12:00-13:00] MEMBER 3. One minute.',
    '',
    'Security. The part worth explaining is at the top, because it is a mistake that is easy to make.',
    'Route middleware asks whether the user is the right kind of person for this area. That is necessary, but it is not sufficient. It confirms the visitor is a faculty member; it says nothing about which faculty member.',
    'So there is a second layer. A policy checks that the student is actually enrolled in a section this lecturer teaches. Without it, any lecturer could open any student profile just by changing the number in the address bar - which is exactly what we demonstrated a moment ago.',
    'Underneath are the standard protections. Every query binds its parameters through the ORM, so SQL injection is not possible. Output is escaped by default. State-changing forms carry a CSRF token. Uploads are restricted by type and size, renamed, and stored outside the source tree. And sign-in attempts are rate limited.',
    'We relied on the framework for these deliberately, because hand-rolling security primitives is where student projects get vulnerabilities.',
  ].join('\n'));
}

// ===================== 10  LESSONS LEARNED =====================
{
  const s = newSlide(false);
  heading(s, 'What we actually learned', 'The technical part was the easy part.');

  const lessons = [
    ['Integration is a data problem', 'We expected to link three applications together. What it required was agreeing one definition of a student and rewriting three foreign keys. Almost no interface code was involved.', TEAL],
    ['Duplication is invisible from inside', 'None of our three systems looked wrong on its own. The redundancy only appeared when we tried to join them and found no key to join on.', GOLD],
    ['Put correctness in the schema', 'A unique constraint caught a mistake in our own test code. Rules held by the database apply no matter which module writes the row.', ATT],
    ['Agree the schema early, together', 'Dividing work by subsystem worked, but the shared students table became a bottleneck. Settling it in one sitting was our best decision.', SUB],
  ];
  lessons.forEach((l, i) => {
    const y = 1.94 + i * 1.14;
    s.addShape(pres.ShapeType.ellipse, { x: M, y: y + 0.12, w: 0.4, h: 0.4, fill: { color: l[2] } });
    s.addText(String(i + 1), { x: M, y: y + 0.165, w: 0.4, h: 0.34, isTextBox: true, margin: 0,
      align: 'center', fontFace: HEAD, fontSize: 14, bold: true, color: l[2] === GOLD ? NAVY : WHITE });
    s.addText(l[0], { x: M + 0.64, y, w: CW - 0.64, h: 0.34, isTextBox: true, margin: 0,
      fontFace: HEAD, fontSize: 17, bold: true, color: NAVY });
    s.addText(l[1], { x: M + 0.64, y: y + 0.36, w: CW - 0.64, h: 0.62, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 12.5, color: MUTED, lineSpacingMultiple: 1.08 });
  });

  speaker(s, 'MEMBER 3');
  s.addNotes([
    '[13:00-13:45] MEMBER 3. Forty-five seconds - keep it moving.',
    '',
    'Four things we take away, and the pattern across them is that the technical part was the easy part.',
    'First, integration turned out to be a data problem, not an interface problem. We expected to be writing code to make three applications talk to each other. What it actually required was agreeing on one definition of a student and rewriting three foreign keys.',
    'Second, and this surprised us: none of our three systems looked wrong on its own. Each held exactly the fields it needed and was perfectly consistent internally. The duplication only became visible when we tried to ask a question spanning two of them and discovered there was no key to join on. Redundancy is hard to see from inside the system that contains it.',
    'Third, put correctness in the schema. A unique constraint we wrote actually caught a mistake in our own test code - it stopped us creating two class meetings at the same time, which cannot happen in reality.',
    'And fourth, dividing the work by subsystem worked well, but the shared students table became a bottleneck because any change to it affected all three of us. Settling that schema early, in one sitting, was the single best decision we made.',
  ].join('\n'));
}
