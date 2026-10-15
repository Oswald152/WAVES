# WAVES

WAVES is a starter internal workspace built for the Wemakespace product brief using PHP, MySQL, HTML, CSS, and JavaScript.

## Stack

- PHP 8+
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript

## Local setup

1. Start Apache and MySQL in XAMPP.
2. Open phpMyAdmin and make sure the database is named `waves`.
3. Import the file `database/waves_schema.sql`.
4. Open the project in your browser at `http://localhost/WAVES`.
5. Interns can sign in at `http://localhost/WAVES/login.php`. Administrators can sign in at `http://localhost/WAVES/admin-login.php` using an admin account created in the database or by another administrator.

## Project structure

- `index.php` – entry point
- `login.php` – intern sign-in page
- `admin-login.php` – administrator sign-in page
- `dashboard.php` – summary dashboard
- `work.php` – assigned work and tasks
- `projects.php` – projects overview
- `onboarding.php` – onboarding checklist progress
- `learning.php` – learning area
- `weekly-report.php` – weekly report workflow
- `feedback.php` – manager/mentor feedback
- `announcements.php` – company updates
- `people.php` – organization people list
- `includes/` – shared PHP logic
- `assets/` – CSS and JavaScript
- `database/waves_schema.sql` – database schema and sample data

## Notes

This is a practical starter that matches the Phase 1 WAVES brief without using the framework stack suggested in the original brief. It is intended to be a useful base for prototyping and iteration.
