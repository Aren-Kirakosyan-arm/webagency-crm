# WebAgency CRM

A lightweight CRM for a web agency built with PHP 8.3, MySQL, HTML, CSS, and vanilla JavaScript. No framework is required.

## Features
- User registration and login with password hashing and CSRF protection.
- Dashboard with client, project, and open-task statistics.
- Client management: create, search, edit, and delete.
- Project management: create, edit, delete, client association, budget, status, progress, and deadline.
- Task management: create, edit, delete, project association, priority, due date, search, and status filtering.
- Responsive interface and MySQL PDO prepared statements.

## Setup (MAMP / Windows)
1. Copy the `webagency-crm` folder into `C:\MAMP\htdocs`.
2. Create a MySQL database by importing `database/schema.sql` in phpMyAdmin.
3. Check `includes/config.php` and set the correct MySQL host, port, username, and password for your MAMP installation.
4. Enable `pdo_mysql` in the PHP configuration used by both MAMP and the command line.
5. Open `http://localhost:81/webagency-crm/register.php` and create an account.
6. Open `http://localhost:81/webagency-crm/index.php`.

## Validation
Run `php -l index.php`, `php -l clients.php`, `php -l projects.php`, and `php -l tasks.php` from the project directory.

## Security notes
Do not commit real credentials. Keep `includes/config.php` local if it contains environment-specific credentials. Use `.env.example` as a template only; this project currently reads its connection settings from `includes/config.php`.
