HOUSEHOLD PROFILING SYSTEM (PHP + MySQL for XAMPP)

SETUP
1. Install XAMPP and start Apache and MySQL in the XAMPP Control Panel.
2. Copy this "household" folder into C:\xampp\htdocs\  (so you have C:\xampp\htdocs\household).
3. Open http://localhost/household/install.php  (creates database "household_db", tables and 3 starter users).
4. DELETE install.php afterwards.
5. Open http://localhost/household/ and sign in.

UPGRADING AN EXISTING INSTALL (adds household IDs, duplicate tracker, audit log)
  Open http://localhost/household/migrate.php once, then DELETE migrate.php.
  (Or paste upgrade.sql into phpMyAdmin's SQL tab. Needs MariaDB, which XAMPP includes.)
  Replace all old files with the new ones first; keep your edited config.php if you changed it.

STARTER LOGINS (change immediately in Users)
  admin / admin123      worker / worker123      official / official123

ROLES
  Administrator: everything, including delete, users and thresholds
  Social Worker / Encoder: add/edit households, dashboard, print
  Barangay Official: view-only dashboard, list and print

NOTES
- Requires PHP 8.0+ (XAMPP 8.x). Database settings are in config.php.
- Income is scored manually (0/1/2) on the score sheet; no poverty threshold is used.
- Back up the database via http://localhost/phpmyadmin -> household_db -> Export.
- For use over a network, use HTTPS and a strong MySQL root password.

NEW FEATURES
- Household ID (HH-2026-0001) on lists and printed sheets.
- Duplicate warning when saving a household whose head has the same name and birthdate as an existing one (tick "save anyway" to override).
- Duplicates page (admin): same head name+birthdate, same PhilHealth number, same family member in different households. Actions: View, Keep this/delete others, Not a duplicate.
- Audit log page (admin): ADD, EDIT (shows old -> new values), DELETE, MERGE, PRINT, LOGIN, USER changes, with user, time and IP. Filter by action/user and print.
- Printing is logged when the print dialog opens (button or Ctrl+P) on a data sheet or the household list. A browser cannot tell whether the page was actually sent to the printer.
- Search (Households page) now looks across everything: household ID, barangay, head name, family member names, occupations, education, remarks (SC/PWD/4P's...), contact, PhilHealth #, religion, house type, interviewer, dates. Several words must ALL match, but each word can match a different field (e.g. "santos teacher").
- Export CSV (Households page) downloads exactly the list currently shown (search + barangay + classification filters), opens in Excel (UTF-8), and includes all form fields, the 8 scores, total, classification and a family-members column. Each export is recorded in the Audit log (EXPORT).
- Households page search is live: results update as you type (and when you change a filter) without reloading; the URL, Print and Export CSV always follow the current search.
- Printing long lists: the household list and audit log print in landscape with the column headings repeated on every page, rows never split across pages, a print heading (title, date, who printed, filters) and "Page X of Y" at the bottom (page numbers need a recent Chrome/Edge; in print preview you can also turn on the browser's own headers/footers).
- Audit log: From/To date filters plus quick ranges (Today, Last 7 days, Last 30 days, This month). "Print all N matching" prints every matching entry (up to 5000), not just the 50 on screen. Printing the log is itself recorded.
