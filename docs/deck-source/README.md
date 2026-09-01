# Presentation source

```bash
npm install pptxgenjs
node build.js
```

Output: `../EAMU-MIS-Group-Presentation.pptx`

## Quality checks

No LibreOffice is installed here, so the deck cannot be rendered to images. These
two scripts check what a visual pass would have caught:

```bash
python qa_layout.py      # off-slide shapes, tight margins, overlapping text
python qa_overflow.py    # estimated text overflow, by simulating word wrap
```

Both must report clean before the deck is considered finished.
