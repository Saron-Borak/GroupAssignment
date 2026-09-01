const D = require('./deck.js');
const { pres, newSlide, heading, speaker, card,
  NAVY, NAVY2, TEAL, GOLD, INK, MUTED, LINE, TINT, WHITE, ATT, SUB, CMP, RED,
  HEAD, BODY, W, Hh, M, CW } = D;

// ===================== 11  FUTURE SCOPE =====================
{
  const s = newSlide(false);
  heading(s, 'What we would build next', 'Ordered by value against effort. The first column is close to free.');

  const groups = [
    ['READY TO BUILD', TEAL, [
      ['Automatic notification', 'Email a student and adviser when attendance drops below the rule. The calculation exists; only delivery is missing.'],
      ['Bulk import', 'Load an intake cohort from a spreadsheet, with a validation report before committing.'],
    ]],
    ['REAL WORK', GOLD, [
      ['Audit trail', 'Record who changed which field and when, which a registry system would normally require.'],
      ['Document attachments', 'Store certificates and identity documents, extending the existing upload handling.'],
    ]],
    ['LARGER SCOPE', MUTED, [
      ['Full module interfaces', 'Bring the complete administrative screens of all three subsystems into this application.'],
      ['Programme progression', 'Track credits earned and year of study, enabling progression reporting.'],
    ]],
  ];
  const cw = (CW - 0.64) / 3;
  groups.forEach((g, i) => {
    const x = M + i * (cw + 0.32);
    s.addShape(pres.ShapeType.roundRect, { x, y: 1.9, w: cw, h: 3.5, rectRadius: 0.05,
      fill: { color: TINT }, line: { color: LINE, width: 1 } });
    s.addShape(pres.ShapeType.ellipse, { x: x + 0.28, y: 2.16, w: 0.26, h: 0.26, fill: { color: g[1] } });
    s.addText(g[0], { x: x + 0.66, y: 2.16, w: cw - 0.94, h: 0.28, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 10, bold: true, charSpacing: 1.4, color: g[1] });
    g[2].forEach((it, j) => {
      const y = 2.68 + j * 1.28;
      s.addText(it[0], { x: x + 0.28, y, w: cw - 0.56, h: 0.32, isTextBox: true, margin: 0,
        fontFace: HEAD, fontSize: 14, bold: true, color: NAVY });
      s.addText(it[1], { x: x + 0.28, y: y + 0.34, w: cw - 0.56, h: 0.86, isTextBox: true, margin: 0,
        fontFace: BODY, fontSize: 11.5, color: MUTED, lineSpacingMultiple: 1.08 });
    });
  });

  s.addText('The highest-value addition is notification: the system already knows who is at risk, it just does not tell anyone yet.',
    { x: M, y: 5.62, w: CW, h: 0.44, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 13, italic: true, color: NAVY });

  speaker(s, 'MEMBER 3');
  s.addNotes([
    '[13:45-14:15] MEMBER 3. Thirty seconds - keep it brisk.',
    '',
    'Briefly, what comes next.',
    'The left column is nearly free because the hard part already exists. Notification is the clearest example, and it is the line at the bottom: the system already knows exactly who is below the attendance requirement and who has an open complaint. It just does not tell anybody yet. That closes the loop on the original problem.',
    'The middle column is well-defined work: an audit trail, and document attachments extending the upload handling we already have.',
    'The right column is larger - bringing the full interfaces of all three subsystems in, and tracking progression.',
  ].join('\n'));
}

// ===================== 12  CONCLUSION =====================
{
  const s = newSlide(true);
  s.addText('IN CLOSING', { x: M, y: 1.15, w: CW, h: 0.32, isTextBox: true, margin: 0,
    fontFace: BODY, fontSize: 12, bold: true, charSpacing: 2.4, color: TEAL });
  s.addText('Three student tables became one.', { x: M, y: 1.58, w: 11.4, h: 0.62, isTextBox: true, margin: 0,
    fontFace: HEAD, fontSize: 32, bold: true, color: WHITE });
  s.addText('A phone number is stored once. A question spanning attendance and complaints can finally be asked. And the registry can see, for the first time, which records are incomplete.',
    { x: M, y: 2.24, w: 11.4, h: 0.6, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 14.5, color: 'B9C6DB', lineSpacingMultiple: 1.14 });

  const takeaways = [
    ['One shared record', 'Attendance, submission and complaint data all resolve through a single profile.'],
    ['Correctness in the schema', 'Unique constraints and soft deletes protect the record no matter which module writes.'],
    ['Verified, not just shown', '129 automated tests across four levels, with 80 documented cases traced to requirements.'],
  ];
  const cw = (CW - 0.64) / 3;
  takeaways.forEach((t, i) => {
    const x = M + i * (cw + 0.32);
    s.addShape(pres.ShapeType.roundRect, { x, y: 3.06, w: cw, h: 1.75, rectRadius: 0.05,
      fill: { color: NAVY2 }, line: { color: '2C4574', width: 1 } });
    s.addText(t[0], { x: x + 0.28, y: 3.32, w: cw - 0.56, h: 0.5, isTextBox: true, margin: 0,
      fontFace: HEAD, fontSize: 15, bold: true, color: WHITE });
    s.addText(t[1], { x: x + 0.28, y: 3.86, w: cw - 0.56, h: 0.8, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 11.5, color: '9DAAC2', lineSpacingMultiple: 1.1 });
  });

  const figs = [['68', 'requirements delivered'], ['16', 'tables, third normal form'], ['129', 'tests passing'], ['0', 'build steps needed']];
  const fw = (CW - 0.96) / 4;
  figs.forEach((f, i) => {
    const x = M + i * (fw + 0.32);
    s.addText(f[0], { x, y: 5.06, w: fw, h: 0.56, isTextBox: true, margin: 0,
      fontFace: HEAD, fontSize: 32, bold: true, color: TEAL });
    s.addText(f[1], { x, y: 5.64, w: fw, h: 0.36, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 11, color: '8E9EB8' });
  });

  s.addText('Thank you  -  questions welcome', { x: M, y: 6.28, w: 7.0, h: 0.36, isTextBox: true, margin: 0,
    fontFace: HEAD, fontSize: 16, bold: true, color: WHITE });

  speaker(s, 'ALL THREE MEMBERS TAKE QUESTIONS', true);
  s.addNotes([
    '[14:15-14:45] MEMBER 3 closes, then all three take questions.',
    '',
    'To close where we started. We had three systems each holding their own copy of a student. Now three student tables are one.',
    'A phone number is stored once. A question spanning attendance and complaints can finally be asked. And the registry can see, for the first time, which records are incomplete - a question that could not even arise before, because each system stored only the few fields it needed and considered them complete.',
    'Thank you. We are happy to take questions.',
    '',
    'LIKELY QUESTIONS - agree beforehand who answers each:',
    '',
    'Q: Why not have the three systems call each other through an API instead?',
    'A (Member 2): That keeps three copies of the student and adds a synchronisation problem on top. The duplication is the defect; sharing one table removes it rather than managing it.',
    '',
    'Q: What happens to attendance history when a student is archived?',
    'A (Member 1): It is retained. We soft delete, so the profile leaves the active list but every record referencing it survives. There is a test for exactly that.',
    '',
    'Q: Is the attendance percentage stored or calculated?',
    'A (Member 1): Always calculated. A cached value drifts as soon as any module writes without recalculating, and a wrong figure here can bar a student from an exam. It costs one query per module, which we measured and test for.',
    '',
    'Q: How would this scale to a whole university?',
    'A (Member 2): Reporting is already flat - three queries regardless of cohort size. The next limit would be the profile list, which we would address with indexes on the search columns.',
    '',
    'Q: Which parts came from your individual assignments?',
    'A (Member 3): The three subsystems and their business rules. The profile module, the shared schema and the integration layer are new work for this project.',
    '',
    'Q: What was the hardest part?',
    'A (Member 2): Agreeing what a student is. Two of our systems modelled a taught unit differently, one calling it a course and the other a subject, and reconciling that took longer than any of the code.',
  ].join('\n'));
}
