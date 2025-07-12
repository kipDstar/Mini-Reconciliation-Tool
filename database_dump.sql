-- TaskFlow Database Schema and Sample Data
-- Database: SQLite
-- Generated: 2025-01-13

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'user' CHECK (role IN ('admin', 'user')),
    first_name TEXT,
    last_name TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Sessions Table
CREATE TABLE IF NOT EXISTS sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    session_token TEXT UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Projects Table
CREATE TABLE IF NOT EXISTS projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    color TEXT DEFAULT '#667eea',
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Tasks Table
CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'in_progress', 'completed')),
    priority TEXT DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high')),
    due_date DATE,
    due_time TIME,
    project_id INTEGER,
    assigned_to INTEGER,
    created_by INTEGER,
    tags TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Sample Users Data
-- Note: Passwords are hashed using PHP's password_hash() function
-- Default passwords: admin123, user123

INSERT INTO users (username, email, password_hash, role, first_name, last_name, is_active) VALUES 
('admin', 'admin@taskflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'User', 1),
('john', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'John', 'Doe', 1),
('jane', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Jane', 'Smith', 1);

-- Sample Projects Data
INSERT INTO projects (name, color, created_by) VALUES 
('Work Projects', '#667eea', 1),
('Personal', '#48bb78', 1),
('Health & Fitness', '#f56565', 1),
('Learning', '#ed8936', 1);

-- Sample Tasks Data
INSERT INTO tasks (title, description, status, priority, due_date, due_time, project_id, assigned_to, created_by, tags) VALUES 
('Complete project proposal', 'Finish the quarterly project proposal for the management team', 'pending', 'high', '2025-01-15', '17:00', 1, 2, 1, 'work, urgent'),
('Review code changes', 'Review and approve the latest code changes from the development team', 'pending', 'medium', '2025-01-14', '10:00', 1, 3, 1, 'development, review'),
('Buy groceries', 'Weekly grocery shopping', 'completed', 'low', '2025-01-13', NULL, 2, 2, 1, 'personal'),
('Team meeting preparation', 'Prepare agenda and materials for the weekly team meeting', 'pending', 'medium', '2025-01-16', '09:00', 1, 2, 1, 'work, meeting'),
('Exercise routine', 'Complete 30-minute workout', 'pending', 'medium', '2025-01-14', '07:00', 3, 3, 1, 'fitness, health'),
('Read chapter 5', 'Read and take notes on chapter 5 of JavaScript book', 'pending', 'low', '2025-01-17', NULL, 4, 2, 1, 'learning, javascript'),
('Call dentist', 'Schedule dental cleaning appointment', 'pending', 'medium', '2025-01-15', NULL, 2, 3, 1, 'health, appointment'),
('Update resume', 'Update resume with recent projects and skills', 'completed', 'medium', '2025-01-12', NULL, 1, 2, 1, 'career, professional');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_tasks_assigned_to ON tasks(assigned_to);
CREATE INDEX IF NOT EXISTS idx_tasks_created_by ON tasks(created_by);
CREATE INDEX IF NOT EXISTS idx_tasks_project_id ON tasks(project_id);
CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status);
CREATE INDEX IF NOT EXISTS idx_tasks_due_date ON tasks(due_date);
CREATE INDEX IF NOT EXISTS idx_sessions_token ON sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_sessions_expires ON sessions(expires_at);
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email); 