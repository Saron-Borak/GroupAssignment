const L = require('./lib.js');
const {
  Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType,
  LevelFormat, Header, Footer, PageNumber, BorderStyle, fs,
  FONT, SIZE, LINE, NAVY, GREY,
} = L;

// Code() and Screenshot() return arrays in places, so the assembled list is
// flattened: an un-flattened entry serialises as an invalid element.
const content = [
  ...require('./s_front.js'),
  ...require('./s1.js'),
  ...require('./s2.js'),
  ...require('./s3.js'),
  ...require('./s3b.js'),
  ...require('./s3c.js'),
  ...require('./s4.js'),
  ...require('./s5.js'),
  ...require('./s6.js'),
  ...require('./s7.js'),
  ...require('./s8.js'),
  ...require('./s9.js'),
].flat(Infinity);

const doc = new Document({
  creator: 'EAMU Educational MIS Group Project',
  title: 'Educational MIS - Student Profile Module - Group Report',
  styles: {
    default: {
      // Brief: Cambria 12pt, 1.15 line spacing.
      document: { run: { font: FONT, size: SIZE, color: '1A1A1A' }, paragraph: { spacing: { line: LINE, after: 140 } } },
      heading1: { run: { font: FONT, size: 32, bold: true, color: NAVY }, paragraph: { spacing: { before: 300, after: 190 }, outlineLevel: 0 } },
      heading2: { run: { font: FONT, size: 26, bold: true, color: NAVY }, paragraph: { spacing: { before: 280, after: 130 }, outlineLevel: 1 } },
      heading3: { run: { font: FONT, size: 23, bold: true, color: '2A3D5C' }, paragraph: { spacing: { before: 230, after: 110 }, outlineLevel: 2 } },
    },
  },
  numbering: {
    config: [
      {
        reference: 'bullets',
        levels: [{
          level: 0, format: LevelFormat.BULLET, text: '\u2022', alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 460, hanging: 260 } } },
        }],
      },
      {
        reference: 'steps',
        levels: [{
          level: 0, format: LevelFormat.DECIMAL, text: '%1.', alignment: AlignmentType.START,
          style: { paragraph: { indent: { left: 460, hanging: 300 } } },
        }],
      },
    ],
  },
  sections: [{
    // Brief: 0.5 inch margins (720 twips).
    properties: { page: { margin: { top: 720, right: 720, bottom: 720, left: 720 } } },
    headers: {
      default: new Header({
        children: [new Paragraph({
          alignment: AlignmentType.RIGHT,
          border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: 'D6DBE4' } },
          spacing: { after: 200 },
          children: [new TextRun({
            text: 'Educational MIS - Student Profile Module  |  East Asia Management University',
            font: FONT, size: 16, color: GREY,
          })],
        })],
      }),
    },
    footers: {
      default: new Footer({
        children: [new Paragraph({
          alignment: AlignmentType.CENTER,
          children: [new TextRun({ children: ['Page ', PageNumber.CURRENT, ' of ', PageNumber.TOTAL_PAGES], font: FONT, size: 16, color: GREY })],
        })],
      }),
    },
    children: content,
  }],
});

Packer.toBuffer(doc).then((buffer) => {
  const out = 'C:/Users/Rakze/Desktop/GroupAssignment/docs/EAMU-MIS-Group-Report.docx';
  fs.mkdirSync('C:/Users/Rakze/Desktop/GroupAssignment/docs', { recursive: true });
  fs.writeFileSync(out, buffer);
  console.log('written:', out);
  console.log('elements:', content.length);
  console.log('size KB:', Math.round(buffer.length / 1024));
});
