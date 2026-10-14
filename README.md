# WebAgency CRM

A responsive CRM dashboard for managing a small web agency's clients and projects.

## Features

- Responsive dashboard UI
- PHP + PDO database connection
- MySQL tables for users, clients, projects, and tasks
- Create projects through a form and persist them in MySQL
- Prepared statements and server-side validation
- Project budget chart using Chart.js

## Tech stack

- PHP 8+
- MySQL / MariaDB
- HTML5, CSS3, JavaScript
- PDO
- Git and GitHub

## Setup

1. Start Apache/PHP and MySQL in your local stack (MAMP or another local PHP environment).
2. Open phpMyAdmin and run `database/schema.sql`.
3. Update database host, port, username, and password in `includes/config.php`.
4. From the project directory, run `php -S localhost:8000` if PHP is available in your terminal.
5. Visit `http://localhost:8000`.

If your local stack uses a different MySQL port, update `$dbPort` in `includes/config.php`.

## Notes

- Dashboard project and client counts are read from MySQL.
- The create-project form creates a client if that client name does not exist, then saves the project.
- Authentication pages are placeholders and are not yet production-ready.
- Use environment variables or a private configuration file for production secrets. Never commit real credentials.
- Chart.js and Google Fonts are loaded from CDNs, so those visual features need internet access.

## Roadmap

- Authentication and role-based access
- Client management CRUD
- Edit/delete project actions
- Task management
- CSRF protection and production deployment hardening
