<?php

class Database {
    private $connection;
    private $db_file;
    
    public function __construct() {
        $this->db_file = __DIR__ . '/../data/tasks.db';
        $this->ensureDataDirectory();
        $this->connect();
        $this->createTables();
    }
    
    private function ensureDataDirectory() {
        $data_dir = dirname($this->db_file);
        if (!is_dir($data_dir)) {
            mkdir($data_dir, 0755, true);
        }
    }
    
    private function connect() {
        try {
            $this->connection = new PDO('sqlite:' . $this->db_file);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    private function createTables() {
        // Users table with authentication
        $sql_users = "
            CREATE TABLE IF NOT EXISTS users (
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
            )
        ";
        
        // Projects table
        $sql_projects = "
            CREATE TABLE IF NOT EXISTS projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                color TEXT DEFAULT '#667eea',
                created_by INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES users(id)
            )
        ";
        
        // Tasks table with user assignment
        $sql_tasks = "
            CREATE TABLE IF NOT EXISTS tasks (
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
            )
        ";
        
        // Task notifications table
        $sql_notifications = "
            CREATE TABLE IF NOT EXISTS task_notifications (
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
            )
        ";
        
        // User sessions table
        $sql_sessions = "
            CREATE TABLE IF NOT EXISTS user_sessions (
                id TEXT PRIMARY KEY,
                user_id INTEGER NOT NULL,
                ip_address TEXT,
                user_agent TEXT,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
        
        try {
            $this->connection->exec($sql_users);
            $this->connection->exec($sql_projects);
            $this->connection->exec($sql_tasks);
            $this->connection->exec($sql_notifications);
            $this->connection->exec($sql_sessions);
            $this->insertSampleData();
        } catch (PDOException $e) {
            throw new Exception('Error creating tables: ' . $e->getMessage());
        }
    }
    
    private function insertSampleData() {
        // Check if data already exists
        $count = $this->connection->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($count > 0) {
            return; // Data already exists
        }
        
        // Insert sample users
        $users_sql = "
            INSERT INTO users (username, email, password, role, first_name, last_name, status) VALUES 
            ('admin', 'admin@taskflow.com', ?, 'admin', 'Administrator', 'User', 'active'),
            ('john_doe', 'john@example.com', ?, 'user', 'John', 'Doe', 'active'),
            ('jane_smith', 'jane@example.com', ?, 'user', 'Jane', 'Smith', 'active'),
            ('bob_wilson', 'bob@example.com', ?, 'user', 'Bob', 'Wilson', 'active'),
            ('alice_brown', 'alice@example.com', ?, 'user', 'Alice', 'Brown', 'active')
        ";
        
        // Hash passwords (password is 'password123' for all users)
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        
        $stmt = $this->connection->prepare($users_sql);
        $stmt->execute([$hashedPassword, $hashedPassword, $hashedPassword, $hashedPassword, $hashedPassword]);
        
        // Insert sample projects
        $projects_sql = "
            INSERT INTO projects (name, description, color, created_by) VALUES 
            ('Website Redesign', 'Complete redesign of company website', '#667eea', 1),
            ('Mobile App Development', 'Develop mobile application for iOS and Android', '#48bb78', 1),
            ('Marketing Campaign', 'Q1 marketing campaign planning and execution', '#f56565', 1),
            ('Database Migration', 'Migrate legacy database to new system', '#ed8936', 1)
        ";
        
        $this->connection->exec($projects_sql);
        
        // Insert sample tasks
        $tasks_sql = "
            INSERT INTO tasks (title, description, status, priority, due_date, due_time, project_id, assigned_to, created_by, tags) VALUES 
            ('Design homepage mockup', 'Create initial homepage design mockup with new branding', 'pending', 'high', '2025-01-20', '17:00', 1, 2, 1, 'design, mockup, homepage'),
            ('Develop user authentication', 'Implement user login and registration system', 'in_progress', 'high', '2025-01-18', '12:00', 1, 3, 1, 'development, auth, backend'),
            ('Write API documentation', 'Document all API endpoints for the new system', 'pending', 'medium', '2025-01-25', '15:00', 1, 4, 1, 'documentation, api'),
            ('Create mobile app wireframes', 'Design wireframes for mobile application screens', 'pending', 'medium', '2025-01-22', '10:00', 2, 2, 1, 'design, mobile, wireframes'),
            ('Set up development environment', 'Configure development environment for mobile app', 'completed', 'low', '2025-01-15', '09:00', 2, 3, 1, 'setup, development, mobile'),
            ('Market research analysis', 'Analyze market trends and competitor analysis', 'pending', 'medium', '2025-01-30', '14:00', 3, 5, 1, 'research, marketing, analysis'),
            ('Create social media content', 'Design content for social media marketing campaign', 'in_progress', 'medium', '2025-01-28', '11:00', 3, 2, 1, 'content, social media, marketing'),
            ('Database schema design', 'Design new database schema for migration', 'pending', 'high', '2025-01-19', '16:00', 4, 4, 1, 'database, schema, migration')
        ";
        
        $this->connection->exec($tasks_sql);
        
        // Insert sample notifications
        $notifications_sql = "
            INSERT INTO task_notifications (task_id, user_id, type, message, is_read, email_sent) VALUES 
            (1, 2, 'task_assigned', 'You have been assigned a new task: Design homepage mockup', 0, 1),
            (2, 3, 'task_assigned', 'You have been assigned a new task: Develop user authentication', 1, 1),
            (3, 4, 'task_assigned', 'You have been assigned a new task: Write API documentation', 0, 1),
            (4, 2, 'task_assigned', 'You have been assigned a new task: Create mobile app wireframes', 1, 1),
            (5, 3, 'task_assigned', 'You have been assigned a new task: Set up development environment', 1, 1)
        ";
        
        $this->connection->exec($notifications_sql);
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception('Query failed: ' . $e->getMessage());
        }
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
}

// Global database instance
function getDatabase() {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db;
}

?>