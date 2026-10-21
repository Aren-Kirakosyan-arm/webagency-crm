# WebAgency CRM

> A lightweight CRM for managing clients, projects, budgets, deadlines, and tasks.

**Stack:** PHP 8.3 · MySQL · PDO · HTML5 · CSS3 · Vanilla JavaScript

## 📸 Preview

<p align="center">
  <img src="https://placehold.co/1200x700/png?text=WebAgency+CRM" alt="WebAgency CRM preview" width="900" />
</p>

> Replace the preview above with a real screenshot from the running application when publishing a final portfolio version.

## Overview

WebAgency CRM is a database-driven CRM built for a small web agency. The project focuses on practical business workflows, a responsive interface, and secure server-side handling without a framework.

## ✨ Features

- 🔐 Registration and login
- 🔒 Password hashing and CSRF protection
- 👥 Client management with search, create, edit, and delete
- 📁 Project management linked to clients
- 💰 Project budgets and progress tracking
- 📅 Deadlines and project status
- ✅ Task management with priorities, due dates, and status filters
- 📊 Dashboard statistics
- 🗄️ MySQL database with PDO prepared statements
- 📱 Responsive interface

## 🧱 Project Structure

```text
webagency-crm/
├── assets/
│   ├── css/
│   └── js/
├── database/
│   └── schema.sql
├── includes/
│   ├── auth.php
│   └── config.php
├── clients.php
├── projects.php
├── tasks.php
├── index.php
├── login.php
├── register.php
└── logout.php
```

## 🚀 Run Locally with MAMP

1. Copy the project into `C:\MAMP\htdocs\webagency-crm`.
2. Start Apache and MySQL in MAMP.
3. Import `database/schema.sql` using phpMyAdmin.
4. Configure the local database connection in `includes/config.php`.
5. Make sure PHP has `pdo_mysql` enabled.
6. Open the project through your local MAMP URL and register an account.

## 🔍 Validation

PHP syntax can be checked with:

```bash
php -l index.php
php -l clients.php
php -l projects.php
php -l tasks.php
```

## 🔐 Security

The project uses password hashing, CSRF protection, sessions, input validation, and PDO prepared statements. Local database credentials should never be committed to GitHub.

## 📌 Status

Personal development project focused on building a practical PHP/MySQL CRM and improving full-stack development skills.
