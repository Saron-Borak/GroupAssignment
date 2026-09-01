const L = require('./lib.js');
const { Paragraph, TextRun, AlignmentType, PageBreak, FONT, SIZE, NAVY, GREY,
        P, Rich, H1, H2, Tbl, TableCaption } = L;

// A ruled writing area for a form the group fills in by hand.
const Lines = (count, width = 10800) => Array.from({ length: count }, () => new Paragraph({
  spacing: { after: 200, line: 276 },
  border: { bottom: { style: 'single', size: 4, color: 'AAB2BF' } },
  children: [new TextRun({ text: ' ', font: FONT, size: SIZE })],
}));

const ratingRow = (label) => [label, '7   6   5   4   3   2   1', ''];

const RATINGS = [
  ['Always attended meetings', 'Never attended meetings'],
  ['Available when needed', 'Unavailable when needed'],
  ['High quality ideas', 'Low quality ideas'],
  ['Dependable', 'Undependable'],
  ['High quality work', 'Low quality work'],
  ['Facilitated goal achievement', 'Hindered goal achievement'],
  ['Did more than a fair share', 'Did less than a fair share'],
  ['Easy to work with', 'Difficult to work with'],
  ['High OVERALL evaluation', 'Low OVERALL evaluation'],
];

const ratingTable = () => Tbl(
  ['Criterion', 'Rating (circle one)', 'Opposite'],
  RATINGS.map(([pos, neg]) => [pos, '7    6    5    4    3    2    1', neg]),
  [3600, 3800, 3400],
);

const evaluationBlock = (title) => [
  P(title, { bold: true, after: 100 }),
  ratingTable(),
  new Paragraph({ spacing: { after: 240 }, children: [] }),
];

module.exports = [
  H1('Appendix C: Meeting Log'),
  P('The brief requires a record of group meetings. Complete one block per meeting, sign it, and include it with the submission. The entries below record the meetings actually held; adjust the dates and add further rows as required.'),

  Tbl(['Field', 'Details'], [
    ['Course', 'Information System in Business'],
    ['Project', 'Educational MIS - Student Profile Module'],
    ['Group members', '[ Member 1 ]   /   [ Member 2 ]   /   [ Member 3 ]'],
  ], [2800, 8000]),
  TableCaption('Meeting log header'),

  Tbl(['#', 'Date and time', 'Issue discussed and who was involved', 'Decision made'], [
    ['1', '[ date ]  __:__ to __:__',
      'Scope of the group project, and how the three individual mini projects relate to it. All members.',
      'Build the Student Profile module as the shared master record, and integrate the three subsystems at the data level rather than rewriting their interfaces.'],
    ['2', '[ date ]  __:__ to __:__',
      'Requirements gathering. Each member listed the student fields held by their own mini project. All members.',
      'Confirmed the duplication of student identity across three systems, and agreed the field list for the shared profile.'],
    ['3', '[ date ]  __:__ to __:__',
      'Database design: normalisation of addresses and guardians, and whether to cache the attendance percentage. All members.',
      'Addresses and guardians become separate tables with a lookup for address type. The attendance percentage is always derived, never stored.'],
    ['4', '[ date ]  __:__ to __:__',
      'Division of implementation work. All members.',
      'Each member integrates the subsystem they originally built. Member 1 takes the profile core, Member 2 reporting and export, Member 3 access control and interface.'],
    ['5', '[ date ]  __:__ to __:__',
      'Review of the first working build; validation rules and the completeness indicator. All members.',
      'Added minimum and maximum age bounds on date of birth, and agreed the five completeness sections.'],
    ['6', '[ date ]  __:__ to __:__',
      'Testing results and defects; preparation of the report and presentation. All members.',
      'Fixed the two defects recorded in Section 5.4. Agreed the presentation split and rehearsal date.'],
  ], [500, 2100, 4100, 4100]),
  TableCaption('Meeting log'),

  P('Assessment of the participation level of each member (out of 10):', { bold: true }),
  Tbl(['Member', 'Participation', 'Signature'], [
    ['[ Member 1 ]', '____ / 10', ''],
    ['[ Member 2 ]', '____ / 10', ''],
    ['[ Member 3 ]', '____ / 10', ''],
  ], [3600, 3600, 3600]),
  TableCaption('Participation assessment'),

  H1('Appendix D: Peer and Self Evaluation'),
  P('Rate yourself and every other member of the team. All members must be rated, including yourself. Ratings are confidential and are seen only by the course instructor, who uses them to judge each member\u2019s contribution to the overall effort.'),

  Tbl(['Field', 'Details'], [
    ['Student / group name', '_______________________________________________'],
    ['Date', '_______________________________________________'],
    ['Module / course / unit', 'Information System in Business'],
    ['Assessment number', '_______________________________________________'],
  ], [3000, 7800]),
  TableCaption('Evaluation header'),

  ...evaluationBlock('First, rate yourself:  ______________________________'),
  ...evaluationBlock('Name of first team member being rated:  ______________________________'),
  ...evaluationBlock('Name of second team member being rated:  ______________________________'),
  ...evaluationBlock('Name of third team member being rated:  ______________________________'),

  H1('Appendix E: Anti-Plagiarism Report'),
  P('The brief requires an anti-plagiarism report as the final appendix. Generate it from the checking service your institution uses, and insert the summary page here.'),
  Rich([
    { text: 'INSERT ANTI-PLAGIARISM REPORT  -  ', bold: true, color: '8A6D0B' },
    { text: 'Upload this report to the plagiarism checker your lecturer specifies, then insert the similarity summary page after this paragraph.', color: '6B5A20' },
  ]),
];
