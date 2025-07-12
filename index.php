<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Task Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Login Screen -->
    <div id="loginScreen" class="login-screen">
        <div class="login-container">
            <div class="login-header">
                <h1><i class="fas fa-tasks"></i> TaskFlow</h1>
                <p>Task Management System</p>
            </div>
            
            <form id="loginForm" class="login-form">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="login-footer">
                <p><strong>Demo Credentials:</strong></p>
                <p>Admin: admin / password123</p>
                <p>User: john_doe / password123</p>
            </div>
        </div>
    </div>

    <!-- Main Application -->
    <div id="appContainer" class="app-container" style="display: none;">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-tasks"></i> TaskFlow</h1>
                <div class="user-info">
                    <span id="currentUser">Welcome User</span>
                    <span id="userRole" class="user-role">user</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="#" class="nav-link active" data-view="dashboard">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a></li>
                    <li><a href="#" class="nav-link" data-view="my-tasks">
                        <i class="fas fa-tasks"></i> My Tasks
                    </a></li>
                    <li id="allTasksNav" style="display: none;"><a href="#" class="nav-link" data-view="all-tasks">
                        <i class="fas fa-list"></i> All Tasks
                    </a></li>
                    <li><a href="#" class="nav-link" data-view="notifications">
                        <i class="fas fa-bell"></i> Notifications
                        <span id="notificationCount" class="notification-badge" style="display: none;"></span>
                    </a></li>
                    <li id="usersNav" style="display: none;"><a href="#" class="nav-link" data-view="users">
                        <i class="fas fa-users"></i> Users
                    </a></li>
                    <li><a href="#" class="nav-link" data-view="projects">
                        <i class="fas fa-project-diagram"></i> Projects
                    </a></li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <button id="logoutBtn" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" id="mobileMenuBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 id="pageTitle">Dashboard</h2>
                </div>
                
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search tasks...">
                    </div>
                    <button class="btn-primary" id="addTaskBtn" style="display: none;">
                        <i class="fas fa-plus"></i> Add Task
                    </button>
                    <button class="btn-secondary" id="addUserBtn" style="display: none;">
                        <i class="fas fa-user-plus"></i> Add User
                    </button>
                </div>
            </header>

            <!-- Dashboard View -->
            <div id="dashboard" class="view active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalTasks">0</h3>
                            <p>Total Tasks</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="pendingTasks">0</h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon progress">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="inProgressTasks">0</h3>
                            <p>In Progress</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="completedTasks">0</h3>
                            <p>Completed</p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-sections">
                    <div class="section">
                        <h3>Recent Tasks</h3>
                        <div id="recentTasks" class="task-list">
                            <!-- Recent tasks will be loaded here -->
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3>Overdue Tasks</h3>
                        <div id="overdueTasks" class="task-list">
                            <!-- Overdue tasks will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Tasks View -->
            <div id="my-tasks" class="view">
                <div class="filters-bar">
                    <div class="filter-group">
                        <select id="myTasksStatusFilter" class="filter-select">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                        
                        <select id="myTasksPriorityFilter" class="filter-select">
                            <option value="">All Priority</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>
                
                <div id="myTasksList" class="task-list">
                    <!-- My tasks will be loaded here -->
                </div>
            </div>

            <!-- All Tasks View (Admin only) -->
            <div id="all-tasks" class="view">
                <div class="filters-bar">
                    <div class="filter-group">
                        <select id="allTasksStatusFilter" class="filter-select">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                        
                        <select id="allTasksPriorityFilter" class="filter-select">
                            <option value="">All Priority</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                        
                        <select id="assignedToFilter" class="filter-select">
                            <option value="">All Users</option>
                        </select>
                    </div>
                </div>
                
                <div id="allTasksList" class="task-list">
                    <!-- All tasks will be loaded here -->
                </div>
            </div>

            <!-- Notifications View -->
            <div id="notifications" class="view">
                <div id="notificationsList" class="notifications-list">
                    <!-- Notifications will be loaded here -->
                </div>
            </div>

            <!-- Users View (Admin only) -->
            <div id="users" class="view">
                <div id="usersList" class="users-list">
                    <!-- Users will be loaded here -->
                </div>
            </div>

            <!-- Projects View -->
            <div id="projects" class="view">
                <div class="projects-header">
                    <button class="btn-primary" id="addProjectBtn">
                        <i class="fas fa-plus"></i> Add Project
                    </button>
                </div>
                <div id="projectsList" class="projects-list">
                    <!-- Projects will be loaded here -->
                </div>
            </div>
        </main>
    </div>

    <!-- Task Modal -->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="taskModalTitle">Add New Task</h3>
                <button class="modal-close" id="closeTaskModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="taskForm" class="modal-body">
                <input type="hidden" id="taskId">
                
                <div class="form-group">
                    <label for="taskTitle">Task Title *</label>
                    <input type="text" id="taskTitle" name="title" required placeholder="Enter task title">
                </div>
                
                <div class="form-group">
                    <label for="taskDescription">Description</label>
                    <textarea id="taskDescription" name="description" rows="3" placeholder="Enter task description"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="taskPriority">Priority</label>
                        <select id="taskPriority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="taskAssignedTo">Assign To *</label>
                        <select id="taskAssignedTo" name="assigned_to" required>
                            <option value="">Select User</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="taskDueDate">Due Date</label>
                        <input type="date" id="taskDueDate" name="due_date">
                    </div>
                    
                    <div class="form-group">
                        <label for="taskDueTime">Due Time</label>
                        <input type="time" id="taskDueTime" name="due_time">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="taskProject">Project</label>
                    <select id="taskProject" name="project_id">
                        <option value="">No Project</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="taskTags">Tags (comma separated)</label>
                    <input type="text" id="taskTags" name="tags" placeholder="work, urgent, meeting">
                </div>
            </form>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="cancelTask">Cancel</button>
                <button type="submit" form="taskForm" class="btn-primary" id="saveTask">Save Task</button>
            </div>
        </div>
    </div>

    <!-- User Modal (Admin only) -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="userModalTitle">Add New User</h3>
                <button class="modal-close" id="closeUserModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="userForm" class="modal-body">
                <input type="hidden" id="userId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName">First Name *</label>
                        <input type="text" id="firstName" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="lastName">Last Name *</label>
                        <input type="text" id="lastName" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="userUsername">Username *</label>
                    <input type="text" id="userUsername" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="userEmail">Email *</label>
                    <input type="email" id="userEmail" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="userPassword">Password *</label>
                    <input type="password" id="userPassword" name="password" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="userRole">Role</label>
                        <select id="userRole" name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="userStatus">Status</label>
                        <select id="userStatus" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </form>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="cancelUser">Cancel</button>
                <button type="submit" form="userForm" class="btn-primary" id="saveUser">Save User</button>
            </div>
        </div>
    </div>

    <!-- Project Modal -->
    <div id="projectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Project</h3>
                <button class="modal-close" id="closeProjectModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="projectForm" class="modal-body">
                <div class="form-group">
                    <label for="projectName">Project Name *</label>
                    <input type="text" id="projectName" name="name" required placeholder="Enter project name">
                </div>
                
                <div class="form-group">
                    <label for="projectDescription">Description</label>
                    <textarea id="projectDescription" name="description" rows="3" placeholder="Enter project description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="projectColor">Color</label>
                    <div class="color-picker">
                        <input type="color" id="projectColor" name="color" value="#667eea">
                    </div>
                </div>
            </form>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="cancelProject">Cancel</button>
                <button type="submit" form="projectForm" class="btn-primary">Save Project</button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading" class="loading" style="display: none;">
        <div class="spinner"></div>
        <p>Loading...</p>
    </div>

    <!-- Toast Notifications -->
    <div id="toastContainer" class="toast-container"></div>

    <script src="assets/js/app.js"></script>
</body>
</html>