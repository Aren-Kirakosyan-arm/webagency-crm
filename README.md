# WebAgency CRM

Responsive PHP + MySQL CRM starter project.

## Features
- User registration and login
- Password hashing using `password_hash()` and verification using `password_verify()`
- Session protection and CSRF tokens
- Logout via POST
- Dashboard with project/client/task statistics
- Create projects and clients
- Responsive interface

## Setup with MAMP
1. Copy this folder to `C:\MAMP\htdocs\webagency-crm`.
2. Start Apache and MySQL in MAMP.
3. Open phpMyAdmin and run `database/schema.sql`.
4. Check the host, port, database username, and password in `includes/config.php`.
5. Open `http://localhost/webagency-crm/register.php`.
6. Register your account, then use the dashboard.

## Git
```powershell
git add .
git commit -m "Add secure user authentication"
git push
```

## Security note
For public hosting, use HTTPS, rate-limit login attempts, configure production credentials securely, and review authorization rules for client/project ownership before multi-user deployment.
