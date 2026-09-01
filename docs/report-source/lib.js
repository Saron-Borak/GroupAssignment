const {
  Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType,
  Table, TableRow, TableCell, WidthType, ShadingType, BorderStyle,
  PageBreak, TableOfContents, LevelFormat, Header, Footer, PageNumber,
} = require('docx');
const fs = require('fs');

// Brief: Cambria 12pt, 0.5 inch margins, 1.15 line spacing, APA referencing.
const FONT = 'Cambria';
const SIZE = 24;          // 12pt in half-points
const LINE = 276;         // 1.15 spacing in twentieths of a point
const NAVY = '14243D';
const GREY = '555F6E';

// 0.5in margins on Letter/A4 => usable width in DXA (twips)
const PAGE_W = 12240 - 720 - 720;   // Letter width minus 0.5in each side

const P = (text, o = {}) => new Paragraph({
  spacing: { after: o.after ?? 140, line: LINE },
  alignment: o.align,
  indent: o.indent,
  children: [new TextRun({
    text, font: FONT, size: o.size ?? SIZE,
    bold: o.bold, italics: o.italics, color: o.color,
  })],
});

const Rich = (runs, o = {}) => new Paragraph({
  spacing: { after: o.after ?? 140, line: LINE },
  children: runs.map(r => typeof r === 'string'
    ? new TextRun({ text: r, font: FONT, size: SIZE })
    : new TextRun({ font: FONT, size: SIZE, ...r })),
});

const H1 = (t) => new Paragraph({
  text: t, heading: HeadingLevel.HEADING_1, pageBreakBefore: true,
  spacing: { before: 200, after: 180, line: LINE },
});
const H2 = (t) => new Paragraph({ text: t, heading: HeadingLevel.HEADING_2, spacing: { before: 260, after: 130, line: LINE } });
const H3 = (t) => new Paragraph({ text: t, heading: HeadingLevel.HEADING_3, spacing: { before: 220, after: 110, line: LINE } });

const Bullet = (t) => new Paragraph({
  text: t, numbering: { reference: 'bullets', level: 0 },
  spacing: { after: 70, line: LINE },
});
const Num = (t) => new Paragraph({
  text: t, numbering: { reference: 'steps', level: 0 },
  spacing: { after: 70, line: LINE },
});

const Code = (lines) => lines.map((l, i) => new Paragraph({
  shading: { type: ShadingType.CLEAR, color: 'auto', fill: 'F2F4F8' },
  spacing: { before: i === 0 ? 110 : 0, after: i === lines.length - 1 ? 160 : 0, line: 240 },
  children: [new TextRun({ text: l || ' ', font: 'Consolas', size: 18 })],
}));

const cell = (content, { w, bold, fill, align } = {}) => new TableCell({
  width: { size: w, type: WidthType.DXA },
  shading: fill ? { type: ShadingType.CLEAR, color: 'auto', fill } : undefined,
  margins: { top: 60, bottom: 60, left: 90, right: 90 },
  children: (Array.isArray(content) ? content : [content]).map(t => new Paragraph({
    alignment: align,
    spacing: { after: 0, line: 240 },
    children: [new TextRun({ text: String(t), font: FONT, size: 19, bold })],
  })),
});

const Tbl = (headings, rows, widths) => {
  const cols = widths || headings.map(() => Math.floor(PAGE_W / headings.length));
  return new Table({
    width: { size: PAGE_W, type: WidthType.DXA },
    columnWidths: cols,
    rows: [
      new TableRow({
        tableHeader: true,
        children: headings.map((h, i) => cell(h, { w: cols[i], bold: true, fill: 'E7EBF2' })),
      }),
      ...rows.map(r => new TableRow({ children: r.map((c, i) => cell(c, { w: cols[i] })) })),
    ],
  });
};

let tableNo = 0;
const TableCaption = (text) => {
  tableNo += 1;
  return new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 60, after: 200, line: LINE },
    children: [new TextRun({ text: `Table ${tableNo}. ${text}`, font: FONT, size: 19, italics: true, color: GREY })],
  });
};

let figNo = 0;
const FigCaption = (text) => {
  figNo += 1;
  return new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 60, after: 200, line: LINE },
    children: [new TextRun({ text: `Figure ${figNo}. ${text}`, font: FONT, size: 19, italics: true, color: GREY })],
  });
};

const Screenshot = (instruction) => new Paragraph({
  shading: { type: ShadingType.CLEAR, color: 'auto', fill: 'FFF6DA' },
  border: {
    top: { style: BorderStyle.SINGLE, size: 6, color: 'E0C060' },
    bottom: { style: BorderStyle.SINGLE, size: 6, color: 'E0C060' },
    left: { style: BorderStyle.SINGLE, size: 6, color: 'E0C060' },
    right: { style: BorderStyle.SINGLE, size: 6, color: 'E0C060' },
  },
  spacing: { before: 180, after: 60, line: LINE },
  children: [
    new TextRun({ text: 'INSERT SCREENSHOT  -  ', font: FONT, size: 19, bold: true, color: '8A6D0B' }),
    new TextRun({ text: instruction, font: FONT, size: 19, color: '6B5A20' }),
  ],
});

// APA hanging-indent reference entry.
const Ref = (text) => new Paragraph({
  spacing: { after: 130, line: LINE },
  indent: { left: 720, hanging: 720 },
  children: [new TextRun({ text, font: FONT, size: SIZE })],
});

module.exports = {
  Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType,
  TableOfContents, LevelFormat, Header, Footer, PageNumber, PageBreak,
  BorderStyle, ShadingType, fs,
  FONT, SIZE, LINE, NAVY, GREY, PAGE_W,
  P, Rich, H1, H2, H3, Bullet, Num, Code, Tbl, TableCaption, FigCaption, Screenshot, Ref,
};
