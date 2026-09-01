# Report source

The group report is generated from these scripts so it can be rebuilt after a
change to the system rather than edited by hand and left to drift.

`schema.txt` is a dump of the live database columns; the table designs in
section 3.2.3 are generated from it, so they cannot disagree with the real schema.

```bash
npm install docx
node build.js
```

Output: `../EAMU-MIS-Group-Report.docx`

Formatting follows the brief: Cambria 12pt, 0.5 inch margins, 1.15 line spacing,
APA references with hanging indents.
