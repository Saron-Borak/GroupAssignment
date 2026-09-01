const pptxgen = require('pptxgenjs');

// Palette: institutional navy with a teal accent for the shared profile, plus
// one fixed colour per integrated module so the source of a figure is obvious.
const NAVY = '14243D', NAVY2 = '1D3459', TEAL = '0F8A72', GOLD = 'B8860B';
const INK = '1A2333', MUTED = '68738A', LINE = 'D9DEE7', TINT = 'F4F7FA', WHITE = 'FFFFFF';
const ATT = '0F8A72', SUB = '2F6FB5', CMP = 'B8860B', RED = 'C0392B';
const HEAD = 'Cambria', BODY = 'Calibri';
const W = 13.333, Hh = 7.5, M = 0.7, CW = W - M * 2;

const pres = new pptxgen();
pres.layout = 'LAYOUT_WIDE';
pres.title = 'Educational MIS - Student Profile Module';

let n = 0;
function newSlide(dark) {
  const s = pres.addSlide();
  s.background = { color: dark ? NAVY : WHITE };
  n += 1;
  if (n > 1) {
    s.addText(String(n), { x: W - 1.0, y: Hh - 0.5, w: 0.5, h: 0.3, isTextBox: true, margin: 0,
      align: 'right', fontFace: BODY, fontSize: 10, color: dark ? '5B6B85' : 'A9B2C1' });
  }
  return s;
}

// Presenter tag, so each slide states who is speaking.
function speaker(s, who, dark) {
  s.addText(who, { x: M, y: Hh - 0.74, w: 5.5, h: 0.3, isTextBox: true, margin: 0,
    fontFace: BODY, fontSize: 10.5, bold: true, charSpacing: 1,
    color: dark ? '7D8DA8' : MUTED });
}

function heading(s, text, sub, dark) {
  s.addText(text, { x: M, y: 0.44, w: CW, h: 0.62, isTextBox: true, margin: 0,
    fontFace: HEAD, fontSize: 31, bold: true, color: dark ? WHITE : NAVY });
  if (sub) {
    s.addText(sub, { x: M, y: 1.08, w: CW, h: 0.34, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 14, italic: true, color: dark ? 'A9B7CE' : MUTED });
  }
}

function card(s, o) {
  s.addShape(pres.ShapeType.roundRect, { x: o.x, y: o.y, w: o.w, h: o.h, rectRadius: 0.05,
    fill: { color: o.fill || TINT }, line: { color: o.border || LINE, width: 1 } });
  let ty = o.y + 0.24;
  if (o.kicker) {
    s.addText(o.kicker, { x: o.x + 0.26, y: ty, w: o.w - 0.52, h: 0.24, isTextBox: true, margin: 0,
      fontFace: BODY, fontSize: 10, bold: true, charSpacing: 1.2, color: o.kickerColor || TEAL });
    ty += 0.3;
  }
  if (o.title) {
    s.addText(o.title, { x: o.x + 0.26, y: ty, w: o.w - 0.52, h: o.titleH || 0.34, isTextBox: true, margin: 0,
      fontFace: HEAD, fontSize: o.titleSize || 15.5, bold: true, color: NAVY });
    ty += (o.titleH || 0.34) + 0.06;
  }
  if (o.body) {
    const rows = Array.isArray(o.body) ? o.body : [o.body];
    s.addText(rows.map((t, i) => ({ text: t, options: { bullet: rows.length > 1, breakLine: i < rows.length - 1 } })), {
      x: o.x + 0.26, y: ty, w: o.w - 0.52, h: o.y + o.h - ty - 0.18, isTextBox: true, margin: 0,
      valign: 'top', fontFace: BODY, fontSize: o.bodySize || 12.5, color: INK,
      lineSpacingMultiple: 1.08, paraSpaceAfter: rows.length > 1 ? 6 : 0 });
  }
}

module.exports = { pres, newSlide, heading, speaker, card,
  NAVY, NAVY2, TEAL, GOLD, INK, MUTED, LINE, TINT, WHITE, ATT, SUB, CMP, RED,
  HEAD, BODY, W, Hh, M, CW, count: () => n };
