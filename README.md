# TaskFlow - Task Management System

A modern, role-based task management application built with PHP, JavaScript, and SQLite.

---

## Table of Contents

- [Features](#features)
- [How It Works](#how-it-works)
- [Setup & Installation](#setup--installation)
- [Default Users](#default-users)
- [User Roles & Permissions](#user-roles--permissions)
- [API Endpoints](#api-endpoints)
- [Database Schema](#database-schema)
- [Security Features](#security-features)
- [Email Notifications](#email-notifications)
- [File Structure](#file-structure)
- [Troubleshooting](#troubleshooting)
- [Development & Customization](#development--customization)
- [License](#license)

---

## Features

- **Authentication & User Management**
  - Secure login/logout with session tokens
  - Role-based access: Admin and User
  - Admins can manage users (add, edit, delete, activate/deactivate)
- **Task Management**
  - Create, edit, delete tasks
  - Assign tasks (admins can assign to anyone, users to themselves)
  - Task status: Pending, In Progress, Completed
  - Priority: High, Medium, Low
  - Due dates and times
  - Tagging and project grouping
- **Project Management**
  - Create, edit, delete projects
  - Color-coded projects
  - Filter tasks by project
- **Dashboard & Analytics**
  - See total, completed, pending, and overdue tasks
  - View today's and upcoming tasks
  - Search and filter tasks
- **Email Notifications**
  - (Optional) Email sent when a task is assigned

---

## How It Works

### User Experience

- **Login:** Users log in at `/login.php` using their credentials.
- **Dashboard:** After login, users see a dashboard with stats and their tasks.
- **Task Management:** Users can create tasks (assigned to themselves). Admins can assign tasks to any user.
- **Project Management:** Tasks can be grouped into projects for better organization.
- **User Management:** Admins can manage users via the Users tab.
- **Role-Based UI:** Admin-only features are hidden from regular users.

### Technical Flow

- **Frontend:** All UI is in `index.php` and handled by `assets/js/script.js`.
- **Backend:** All data is served via PHP API endpoints in `/api/`.
- **Database:** SQLite file at `data/tasks.db` (auto-created on first run).
- **Authentication:** Uses session tokens stored in localStorage and sent via HTTP headers.

---

## Setup & Installation

### Prerequisites

- PHP 7.4 or higher (with SQLite extension enabled)
- Any web server (Apache, Nginx, or PHP built-in server)
- Git (optional, for cloning)

### Quick Start

1. **Clone or Download the Project**
   ```bash
   git clone <repository-url>
   cd Mini-Reconciliation-Tool
   ```

2. **Start the PHP Server**
   ```bash
   php -S localhost:8000
   ```
   Or configure your web server to point to this directory.

3. **Set Permissions**
   - Make sure the `data/` directory is writable by the web server:
     ```bash
     chmod 775 data
     ```

4. **Access the App**
   - Open your browser and go to: [http://localhost:8000/login.php](http://localhost:8000/login.php)
   - The database and sample data will be created automatically on first run.

5. **Test Authentication**
   - Visit [http://localhost:8000/test_auth.php](http://localhost:8000/test_auth.php) to verify the authentication system.

---

## Default Users

| Username | Password  | Role  | Description           |
|----------|-----------|-------|-----------------------|
| admin    | admin123  | Admin | Full system access    |
| john     | user123   | User  | Regular user access   |
| jane     | user123   | User  | Regular user access   |

---

## User Roles & Permissions

### Admin
- Manage users (add, edit, delete, activate/deactivate)
- Assign tasks to any user
- View and manage all tasks and projects
- Delete any task or project

### User
- View and update only their own tasks
- Create tasks (assigned to themselves)
- Cannot manage users or assign tasks to others

---

## API Endpoints

- **Authentication**
  - `POST /api/auth_endpoints.php?action=login` (login)
  - `POST /api/auth_endpoints.php?action=logout` (logout)
  - `GET /api/auth_endpoints.php?action=me` (current user info)
- **User Management (Admin only)**
  - `GET /api/auth_endpoints.php?action=users`
  - `POST /api/auth_endpoints.php?action=users`
  - `PUT /api/auth_endpoints.php?action=users/update`
  - `DELETE /api/auth_endpoints.php?action=users/delete`
- **Tasks**
  - `GET /api/tasks.php`
  - `POST /api/tasks.php`
  - `PUT /api/tasks.php`
  - `DELETE /api/tasks.php?id={id}`
- **Projects**
  - `GET /api/projects.php`
  - `POST /api/projects.php`
  - `PUT /api/projects.php`
  - `DELETE /api/projects.php?id={id}`

---

## Database Schema

- **users:** id, username, email, password_hash, role, first_name, last_name, is_active, created_at, updated_at
- **sessions:** id, user_id, session_token, expires_at, created_at
- **tasks:** id, title, description, status, priority, due_date, due_time, project_id, assigned_to, created_by, tags, created_at, updated_at
- **projects:** id, name, color, created_by, created_at

---

## Security Features

- Passwords hashed with bcrypt
- Secure session tokens
- All queries use prepared statements (SQL injection safe)
- XSS protection via input/output sanitization
- Role-based access enforced at API level

---

## Email Notifications

- Email notifications are implemented but **disabled by default**.
- To enable:
  1. Configure your server's mail settings.
  2. Uncomment the `mail()` function in `api/tasks.php`.
  3. Update the sender email address as needed.

---

## File Structure

```
Mini-Reconciliation-Tool/
├── api/
│   ├── auth.php
│   ├── auth_endpoints.php
│   ├── database.php
│   ├── projects.php
│   └── tasks.php
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── data/
│   └── tasks.db
├── config.php
├── index.php
├── login.php
├── test_auth.php
├── .htaccess
├── README.md
└── PROJECT_SUMMARY.md
```

---

## Troubleshooting

- **Database errors:** Ensure `data/` is writable and PHP SQLite extension is enabled.
- **Authentication issues:** Use `test_auth.php` to verify, and check browser console for JS errors.
- **API not found:** Ensure `.htaccess` is present and mod_rewrite is enabled (if using Apache).
- **Permission errors:** Check your user role and login status.

---

## Development & Customization

- All business logic is in `/api/` (PHP).
- Frontend logic is in `assets/js/script.js`.
- UI is in `index.php` and styled with `assets/css/style.css`.
- You can add new features by extending the PHP API and updating the JS frontend.

## Current Features

### ✅ Implemented Features
- **Real Email Integration:** SMTP email service with HTML templates
- **Real-time Updates:** Server-Sent Events for live task updates
- **File Attachments:** Upload and manage files with tasks (max 10MB)
- **Advanced Email Templates:** Task assignments, reminders, daily digests
- **Live Notifications:** Real-time task and user update notifications

### Planned Features
- **Advanced Analytics:** Productivity metrics, time tracking, performance dashboards
- **Smart Features:** AI-powered suggestions, automatic prioritization
- **Mobile PWA:** Progressive web app with offline support
- **Calendar Integration:** Google Calendar sync, meeting scheduling
- **Team Collaboration:** Workspaces, shared templates, team insights
- **Third-party Integrations:** Slack, GitHub, Google Workspace
- **Automation:** Workflow rules, auto-assignment, escalation

### Technical Roadmap
- **Backend:** API rate limiting, caching, database optimization
- **Frontend:** Component-based architecture, state management
- **Security:** Two-factor authentication, audit logging
- **Performance:** CDN integration, asset optimization

---

## License

MIT License