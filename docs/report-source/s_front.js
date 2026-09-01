const L = require('./lib.js');
const { Paragraph, TextRun, AlignmentType, TableOfContents, PageBreak,
        FONT, SIZE, NAVY, GREY, P, Rich, Tbl } = L;

const line = (text, o = {}) => new Paragraph({
  alignment: AlignmentType.CENTER,
  spacing: { after: o.after ?? 120, line: 276 },
  children: [new TextRun({
    text, font: FONT, size: o.size ?? SIZE, bold: o.bold, color: o.color, italics: o.italics,
  })],
});

module.exports = [
  new Paragraph({ spacing: { after: 900 }, children: [] }),
  line('EAST ASIA MANAGEMENT UNIVERSITY', { bold: true, size: 28, color: NAVY, after: 60 }),
  line('Information System in Business', { size: 24, color: GREY, after: 40 }),
  line('Degree Final Year 4 - 2026 T3', { size: 22, color: GREY, after: 800 }),

  line('GROUP ASSIGNMENT - MAIN PROJECT', { bold: true, size: 22, color: GREY, after: 200 }),
  line('Educational Management Information System', { bold: true, size: 40, color: NAVY, after: 60 }),
  line('Student Profile Module', { bold: true, size: 40, color: NAVY, after: 240 }),
  line('A single student record serving attendance, project submission and complaint management',
    { size: 22, italics: true, color: GREY, after: 700 }),

  Tbl(['Field', 'Details'], [
    ['Group name / number', '[ Group name or number ]'],
    ['Member 1 (Student Profile core)', '[ Your Full Name ]  -  [ Student ID ]'],
    ['Member 2 (Submission integration)', '[ Member 2 Name ]  -  [ Student ID ]'],
    ['Member 3 (Complaint integration)', '[ Member 3 Name ]  -  [ Student ID ]'],
    ['Module / Course', 'Information System in Business'],
    ['Lecturer', '[ Lecturer Name ]'],
    ['Submission date', '[ Date ]'],
  ], [4000, 6800]),

  new Paragraph({ spacing: { before: 500 }, children: [] }),
  line('Built with PHP 8.5, Laravel 13, MySQL / MariaDB and Bootstrap 5',
    { size: 20, italics: true, color: GREY }),
  new Paragraph({ children: [new PageBreak()] }),

  line('Declaration', { bold: true, size: 32, color: NAVY, after: 220 }),
  P('We declare that this report and the accompanying software are the original work of this group. The three integrated subsystems originate in our own individual mini projects and have been re-implemented against a shared data model for this submission. External libraries are listed in Section 2.6 and referenced in full at the end.'),
  new Paragraph({ spacing: { before: 400 }, children: [] }),
  P('Member 1: ______________________________     Signature: ______________     Date: ____________'),
  P('Member 2: ______________________________     Signature: ______________     Date: ____________'),
  P('Member 3: ______________________________     Signature: ______________     Date: ____________'),
  new Paragraph({ children: [new PageBreak()] }),

  line('Table of Contents', { bold: true, size: 32, color: NAVY, after: 220 }),
  new TableOfContents('Contents', { hyperlink: true, headingStyleRange: '1-3' }),
  Rich([{ text: 'To populate this table in Microsoft Word: click it, press F9, and choose "Update entire table".', italics: true, size: 19, color: GREY }], { after: 0 }),
  new Paragraph({ children: [new PageBreak()] }),
];
