# NETFLX GS — Digital Subscription Management System

> Full-stack PHP/MySQL management platform for digital subscription inventory, profiles, customers, sales, expenses, reports, and email notifications.

## Overview
NETFLX GS is a business management web application built with PHP and MySQL. It provides authentication, role-based access, account/profile inventory, customer CRM, sales tracking, expiry monitoring, expense tracking, reporting, exports, and SMTP email workflows.

## Features
- 🔐 Session authentication and role management
- 👤 Registration, login, password recovery, and profile management
- 📦 Account and profile inventory management
- 👥 Customer CRM and sales history
- 💰 Sales, expenses, and reporting
- ⏰ Subscription expiry monitoring
- 📊 Dashboard and reports
- ✉️ SMTP email notifications
- 🛡️ CSRF protection, secure session cookies, prepared SQL statements, and security headers
- 🗄️ MySQL/MariaDB schema and setup script
- 📱 Responsive Netflix-inspired dashboard UI

## Tech Stack
PHP 8+ · MySQL/MariaDB · PDO · HTML5 · CSS3 · JavaScript · PHPMailer · Git/GitHub

## Project Structure
```text
NETFLIX-GS/
├── index.php
├── login.php
├── register.php
├── accounts.php
├── customers.php
├── sales.php
├── expenses.php
├── reports.php
├── expiry.php
├── profile.php
├── export.php
├── forgot_password.php
├── logout.php
├── setup_db.php
├── schema.sql
├── config/
│   ├── auth.php
│   ├── db.php
│   └── mail.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── PHPMailer/
└── uploads/
```

## Local Setup
1. Install PHP 8+ and MySQL/MariaDB.
2. Import `schema.sql` into MySQL/MariaDB.
3. Configure database environment variables using `.env.example`.
4. Configure SMTP credentials locally with environment variables or application settings.
5. Run `php -S localhost:8000` from the project directory.
6. Open `http://localhost:8000`.

## Security
No real SMTP passwords, customer phone numbers, subscription credentials, card details, or production account data are intentionally included in this repository. Use `.env.example` for local configuration. Production secrets should stay outside Git and be supplied through environment variables or a secret manager.

## Portfolio
**Role:** Full-Stack PHP Developer  
**Focus:** PHP, MySQL/PDO, authentication, CRUD, business logic, reporting, email integration, security, and responsive UI.

### CV Description
**NETFLX GS — Digital Subscription Management System:** Developed a full-stack PHP/MySQL platform with authentication, role-based access, subscription inventory, customer and sales management, expense tracking, expiry monitoring, reporting, data export, and SMTP email workflows. Implemented PDO prepared statements, CSRF protection, secure sessions, and responsive dashboard interfaces.

## Developer
**Taoufik El Mouden**

- GitHub: https://github.com/taoufik-el-mouden
- LinkedIn: https://www.linkedin.com/in/el-mouden-taoufik-968088292/
- Portfolio: https://taoufik-el-mouden.xo.je
