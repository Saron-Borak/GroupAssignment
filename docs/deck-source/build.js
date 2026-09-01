const D = require('./deck.js');
require('./sa.js'); require('./sb.js'); require('./sc.js');
require('./sd.js'); require('./se.js'); require('./sf.js');
const out = 'C:/Users/Rakze/Desktop/GroupAssignment/docs/EAMU-MIS-Group-Presentation.pptx';
D.pres.writeFile({ fileName: out }).then(() => {
  console.log('written:', out);
  console.log('slides:', D.count());
});
