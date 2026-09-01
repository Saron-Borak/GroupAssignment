const L = require('./lib.js');
const { P, Rich, H1, H2, Bullet, Tbl, TableCaption, Ref } = L;

module.exports = [
  H1('7. Lessons Learned'),

  H2('7.1 Integration Is a Data Problem, Not an Interface Problem'),
  P('The group assumed integration would mean linking three applications together. What it actually required was simpler and more disruptive: agreeing on one definition of a student and rewriting three foreign keys to point at it. Almost no interface code was involved. An MIS is defined by its shared data model, not by the screens built on it.'),

  H2('7.2 Duplication Is Invisible Until You Try to Join'),
  P('None of the mini projects looked wrong on its own; each held exactly the fields it needed and was internally consistent. The duplication only became visible when we tried to answer a question spanning two systems and found no key to join them on. Redundancy in a schema is rarely obvious from inside the system that contains it.'),

  H2('7.3 Put Correctness in the Schema Where You Can'),
  P('The uniqueness constraint on attendance sessions caught a mistake in our own test code, and the constraint on session and student makes a duplicate record impossible rather than merely unlikely. Rules enforced by the database hold regardless of which subsystem writes the row, and the absence of the GD extension similarly forced a testing approach that works on the actual target machine.'),

  H2('7.4 Working as a Group'),
  P('Dividing work by subsystem meant nobody had to learn another member domain logic from scratch. The cost was that the shared schema became a bottleneck, since any change to the students table affected all three integrations. Settling that schema early, in one sitting, was the most useful decision of the project.'),

  H1('8. Future Scope'),
  P('The following extensions were identified but fell outside the scope of this assignment.'),
  Tbl(['Enhancement', 'Description', 'Effort'], [
    ['Automatic notification', 'Email a student and their adviser when attendance falls below the requirement. The calculation already exists; only delivery is missing.', 'Low'],
    ['Bulk import', 'Load an intake cohort from a spreadsheet, with a validation report before committing.', 'Low'],
    ['Audit trail', 'Record who changed which field of a profile and when, which a registry system would normally require.', 'Medium'],
    ['Document attachments', 'Store scanned certificates and identity documents against the profile, extending the existing upload handling.', 'Medium'],
    ['Full module interfaces', 'Bring the complete administrative screens of the three subsystems into this application, rather than integrating at the data level only.', 'Medium'],
    ['Self-service corrections', 'Allow a student to request a change to their own contact details, subject to registry approval.', 'Medium'],
    ['Programme progression', 'Extend the profile to track credits earned and year of study, enabling progression reporting.', 'High'],
  ], [2200, 6600, 2000]),
  TableCaption('Possible extensions'),

  H1('9. Conclusion'),
  P('The project set out to build the Student Profile module of an educational MIS and to show it could serve as the single source of student identity for three previously independent subsystems. Both were achieved: all twenty-seven functional and eleven non-functional requirements were implemented and verified by forty-six automated tests, and the system runs on stock XAMPP with no build step.'),
  P('The measurable outcome is that three student tables became one. A telephone number is stored in a single place, a question spanning attendance and complaints can now be asked, and the registry can see for the first time which profiles are incomplete. The profile page brings all three modules onto one screen, which none of the original applications could do.'),
  P('The most valuable thing the group learned is that the hard part of an MIS is not the software. It is agreeing what a student is, and then having the discipline to store that once.'),

  H1('References'),
  Ref('Connolly, T., & Begg, C. (2015). Database systems: A practical approach to design, implementation, and management (6th ed.). Pearson Education.'),
  Ref('Institute of Electrical and Electronics Engineers. (1998). IEEE recommended practice for software requirements specifications (IEEE Std 830-1998). IEEE.'),
  Ref('Laravel. (2026). Laravel 13.x documentation. https://laravel.com/docs/13.x'),
  Ref('Laudon, K. C., & Laudon, J. P. (2022). Management information systems: Managing the digital firm (17th ed.). Pearson Education.'),
  Ref('MariaDB Foundation. (2025). MariaDB server documentation. https://mariadb.com/kb/en/documentation/'),
  Ref('Open Worldwide Application Security Project. (2021). OWASP top ten web application security risks. https://owasp.org/www-project-top-ten/'),
  Ref('Otwell, T. (2026). Laravel: The PHP framework for web artisans. https://laravel.com'),
  Ref('Sommerville, I. (2016). Software engineering (10th ed.). Pearson Education.'),
  Ref('The PHP Group. (2025). PHP manual, version 8.5. https://www.php.net/manual/en/'),
];
