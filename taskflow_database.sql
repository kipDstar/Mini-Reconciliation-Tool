-- TaskFlow Task Management System Database Schema
-- Database: SQLite
-- Created: 2025-01-13

-- ============================================
-- Table: users
-- Purpose: Store user accounts with authentication
-- ============================================
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'user' CHECK (role IN ('admin', 'user')),
    first_name TEXT,
    last_name TEXT,
    status TEXT DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- Table: projects
-- Purpose: Organize tasks into projects
-- ============================================
CREATE TABLE projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    color TEXT DEFAULT '#667eea',
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ============================================
-- Table: tasks
-- Purpose: Store task information with user assignment
-- ============================================
CREATE TABLE tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'in_progress', 'completed')),
    priority TEXT DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high')),
    due_date DATE,
    due_time TIME,
    project_id INTEGER,
    assigned_to INTEGER NOT NULL,
    created_by INTEGER NOT NULL,
    tags TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- Table: task_notifications
-- Purpose: Store notifications for task assignments and updates
-- ============================================
CREATE TABLE task_notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL CHECK (type IN ('task_assigned', 'task_updated', 'task_completed')),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT 0,
    email_sent BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- Table: user_sessions
-- Purpose: Manage user login sessions
-- ============================================
CREATE TABLE user_sessions (
    id TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL,
    ip_address TEXT,
    user_agent TEXT,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- Sample Data
-- ============================================

-- Insert sample users (password: 'password123' for all users)
INSERT INTO users (username, email, password, role, first_name, last_name, status) VALUES 
('admin', 'admin@taskflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator', 'User', 'active'),
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'John', 'Doe', 'active'),
('jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Jane', 'Smith', 'active'),
('bob_wilson', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Bob', 'Wilson', 'active'),
('alice_brown', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Alice', 'Brown', 'active');

-- Insert sample projects
INSERT INTO projects (name, description, color, created_by) VALUES 
('Website Redesign', 'Complete redesign of company website with modern UI/UX', '#667eea', 1),
('Mobile App Development', 'Develop mobile application for iOS and Android platforms', '#48bb78', 1),
('Marketing Campaign', 'Q1 marketing campaign planning and execution', '#f56565', 1),
('Database Migration', 'Migrate legacy database to new cloud-based system', '#ed8936', 1);

-- Insert sample tasks
INSERT INTO tasks (title, description, status, priority, due_date, due_time, project_id, assigned_to, created_by, tags) VALUES 
('Design homepage mockup', 'Create initial homepage design mockup with new branding guidelines and user experience improvements', 'pending', 'high', '2025-01-20', '17:00', 1, 2, 1, 'design, mockup, homepage, ui/ux'),
('Develop user authentication', 'Implement secure user login and registration system with password hashing and session management', 'in_progress', 'high', '2025-01-18', '12:00', 1, 3, 1, 'development, auth, backend, security'),
('Write API documentation', 'Document all API endpoints for the new system including request/response examples', 'pending', 'medium', '2025-01-25', '15:00', 1, 4, 1, 'documentation, api, backend'),
('Create mobile app wireframes', 'Design comprehensive wireframes for all mobile application screens and user flows', 'pending', 'medium', '2025-01-22', '10:00', 2, 2, 1, 'design, mobile, wireframes, ux'),
('Set up development environment', 'Configure development environment for mobile app including testing frameworks', 'completed', 'low', '2025-01-15', '09:00', 2, 3, 1, 'setup, development, mobile, environment'),
('Market research analysis', 'Analyze market trends, competitor analysis, and target audience research', 'pending', 'medium', '2025-01-30', '14:00', 3, 5, 1, 'research, marketing, analysis, strategy'),
('Create social media content', 'Design engaging content for social media marketing campaign across all platforms', 'in_progress', 'medium', '2025-01-28', '11:00', 3, 2, 1, 'content, social media, marketing, design'),
('Database schema design', 'Design new database schema for migration with performance optimization considerations', 'pending', 'high', '2025-01-19', '16:00', 4, 4, 1, 'database, schema, migration, optimization'),
('Performance testing', 'Conduct comprehensive performance testing of the new system under various load conditions', 'pending', 'medium', '2025-01-24', '13:00', 1, 3, 1, 'testing, performance, qa, optimization'),
('User training materials', 'Create comprehensive training materials and documentation for end users', 'pending', 'low', '2025-01-26', '16:30', 1, 5, 1, 'training, documentation, user guide');

-- Insert sample notifications
INSERT INTO task_notifications (task_id, user_id, type, message, is_read, email_sent) VALUES 
(1, 2, 'task_assigned', 'You have been assigned a new task: Design homepage mockup', 0, 1),
(2, 3, 'task_assigned', 'You have been assigned a new task: Develop user authentication', 1, 1),
(3, 4, 'task_assigned', 'You have been assigned a new task: Write API documentation', 0, 1),
(4, 2, 'task_assigned', 'You have been assigned a new task: Create mobile app wireframes', 1, 1),
(5, 3, 'task_assigned', 'You have been assigned a new task: Set up development environment', 1, 1),
(6, 5, 'task_assigned', 'You have been assigned a new task: Market research analysis', 0, 1),
(7, 2, 'task_assigned', 'You have been assigned a new task: Create social media content', 0, 1),
(8, 4, 'task_assigned', 'You have been assigned a new task: Database schema design', 1, 1);

-- ============================================
-- Indexes for Performance Optimization
-- ============================================

-- Index on tasks for efficient user-based queries
CREATE INDEX idx_tasks_assigned_to ON tasks(assigned_to);
CREATE INDEX idx_tasks_created_by ON tasks(created_by);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_due_date ON tasks(due_date);

-- Index on user sessions for efficient session management
CREATE INDEX idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_user_sessions_expires_at ON user_sessions(expires_at);

-- Index on notifications for efficient user-based queries
CREATE INDEX idx_notifications_user_id ON task_notifications(user_id);
CREATE INDEX idx_notifications_is_read ON task_notifications(is_read);

-- Index on users for authentication
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_status ON users(status);

-- ============================================
-- Views for Common Queries
-- ============================================

-- View: Task Summary with User and Project Information
CREATE VIEW task_summary AS
SELECT 
    t.id,
    t.title,
    t.description,
    t.status,
    t.priority,
    t.due_date,
    t.due_time,
    t.tags,
    t.created_at,
    t.updated_at,
    assigned_user.username as assigned_username,
    assigned_user.first_name as assigned_first_name,
    assigned_user.last_name as assigned_last_name,
    assigned_user.email as assigned_email,
    creator.username as creator_username,
    creator.first_name as creator_first_name,
    creator.last_name as creator_last_name,
    p.name as project_name,
    p.color as project_color,
    p.description as project_description
FROM tasks t
LEFT JOIN users assigned_user ON t.assigned_to = assigned_user.id
LEFT JOIN users creator ON t.created_by = creator.id
LEFT JOIN projects p ON t.project_id = p.id;

-- View: User Statistics
CREATE VIEW user_stats AS
SELECT 
    u.id,
    u.username,
    u.first_name,
    u.last_name,
    u.role,
    u.status,
    COUNT(DISTINCT t.id) as total_tasks,
    COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
    COUNT(DISTINCT CASE WHEN t.status = 'pending' THEN t.id END) as pending_tasks,
    COUNT(DISTINCT CASE WHEN t.status = 'in_progress' THEN t.id END) as in_progress_tasks,
    COUNT(DISTINCT CASE WHEN t.status = 'pending' AND t.due_date < DATE('now') THEN t.id END) as overdue_tasks
FROM users u
LEFT JOIN tasks t ON u.id = t.assigned_to
GROUP BY u.id, u.username, u.first_name, u.last_name, u.role, u.status;

-- ============================================
-- Database Statistics and Information
-- ============================================

-- Total Records Summary:
-- Users: 5 (1 admin, 4 users)
-- Projects: 4 different project categories
-- Tasks: 10 sample tasks with various statuses and priorities
-- Notifications: 8 sample notification records
-- Task Statuses: pending, in_progress, completed
-- Task Priorities: low, medium, high
-- User Roles: admin, user

-- ============================================
-- Notes for Implementation
-- ============================================

-- 1. All passwords are hashed using PHP's password_hash() function
-- 2. Default password for all demo accounts is 'password123'
-- 3. Foreign key constraints ensure data integrity
-- 4. Check constraints validate enum-like values (status, priority, role)
-- 5. Indexes are created for commonly queried columns
-- 6. Views provide convenient access to joined data
-- 7. Email notifications are tracked with email_sent boolean flag
-- 8. Session management includes IP tracking and expiration
-- 9. Soft delete is not implemented - records are permanently deleted
-- 10. Created/updated timestamps use SQLite's CURRENT_TIMESTAMP

-- ============================================
-- End of TaskFlow Database Schema
-- ============================================