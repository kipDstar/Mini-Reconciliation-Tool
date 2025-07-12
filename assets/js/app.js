class TaskFlowApp {
    constructor() {
        this.currentUser = null;
        this.currentView = 'dashboard';
        this.tasks = [];
        this.users = [];
        this.projects = [];
        this.notifications = [];
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.checkAuthentication();
    }

    bindEvents() {
        // Login form
        document.getElementById('loginForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.login();
        });

        // Logout button
        document.getElementById('logoutBtn').addEventListener('click', () => {
            this.logout();
        });

        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const view = e.currentTarget.dataset.view;
                this.switchView(view);
            });
        });

        // Mobile menu
        document.getElementById('mobileMenuBtn').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('open');
        });

        // Modal events
        this.bindModalEvents();

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', (e) => {
            this.searchTasks(e.target.value);
        });

        // Filter events
        this.bindFilterEvents();
    }

    bindModalEvents() {
        // Task modal
        document.getElementById('addTaskBtn').addEventListener('click', () => {
            this.openTaskModal();
        });

        document.getElementById('closeTaskModal').addEventListener('click', () => {
            this.closeTaskModal();
        });

        document.getElementById('cancelTask').addEventListener('click', () => {
            this.closeTaskModal();
        });

        document.getElementById('taskForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveTask();
        });

        // User modal
        document.getElementById('addUserBtn').addEventListener('click', () => {
            this.openUserModal();
        });

        document.getElementById('closeUserModal').addEventListener('click', () => {
            this.closeUserModal();
        });

        document.getElementById('cancelUser').addEventListener('click', () => {
            this.closeUserModal();
        });

        document.getElementById('userForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveUser();
        });

        // Project modal
        document.getElementById('addProjectBtn').addEventListener('click', () => {
            this.openProjectModal();
        });

        document.getElementById('closeProjectModal').addEventListener('click', () => {
            this.closeProjectModal();
        });

        document.getElementById('cancelProject').addEventListener('click', () => {
            this.closeProjectModal();
        });

        document.getElementById('projectForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveProject();
        });

        // Close modals when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                if (e.target.id === 'taskModal') this.closeTaskModal();
                if (e.target.id === 'userModal') this.closeUserModal();
                if (e.target.id === 'projectModal') this.closeProjectModal();
            }
        });
    }

    bindFilterEvents() {
        // My tasks filters
        document.getElementById('myTasksStatusFilter').addEventListener('change', () => {
            this.filterMyTasks();
        });

        document.getElementById('myTasksPriorityFilter').addEventListener('change', () => {
            this.filterMyTasks();
        });

        // All tasks filters (admin only)
        if (document.getElementById('allTasksStatusFilter')) {
            document.getElementById('allTasksStatusFilter').addEventListener('change', () => {
                this.filterAllTasks();
            });

            document.getElementById('allTasksPriorityFilter').addEventListener('change', () => {
                this.filterAllTasks();
            });

            document.getElementById('assignedToFilter').addEventListener('change', () => {
                this.filterAllTasks();
            });
        }
    }

    async checkAuthentication() {
        try {
            const response = await fetch('api/auth.php?action=check');
            const result = await response.json();

            if (result.success && result.data.authenticated) {
                this.currentUser = result.data;
                this.showApp();
                this.setupUserInterface();
                this.loadInitialData();
            } else {
                this.showLogin();
            }
        } catch (error) {
            console.error('Auth check failed:', error);
            this.showLogin();
        }
    }

    async login() {
        const formData = new FormData(document.getElementById('loginForm'));
        const loginData = Object.fromEntries(formData.entries());

        this.showLoading();

        try {
            const response = await fetch('api/auth.php?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(loginData)
            });

            const result = await response.json();

            if (result.success) {
                this.currentUser = {
                    user_id: result.data.user.id,
                    username: result.data.user.username,
                    role: result.data.user.role
                };
                this.showApp();
                this.setupUserInterface();
                this.loadInitialData();
                this.showToast('Login successful', 'success');
            } else {
                this.showToast(result.message || 'Login failed', 'error');
            }
        } catch (error) {
            this.showToast('Login failed', 'error');
            console.error('Login error:', error);
        }

        this.hideLoading();
    }

    async logout() {
        try {
            await fetch('api/auth.php?action=logout', { method: 'POST' });
            this.currentUser = null;
            this.showLogin();
            this.showToast('Logged out successfully', 'success');
        } catch (error) {
            console.error('Logout error:', error);
        }
    }

    showLogin() {
        document.getElementById('loginScreen').style.display = 'flex';
        document.getElementById('appContainer').style.display = 'none';
        document.getElementById('loginForm').reset();
    }

    showApp() {
        document.getElementById('loginScreen').style.display = 'none';
        document.getElementById('appContainer').style.display = 'flex';
    }

    setupUserInterface() {
        // Update user info
        document.getElementById('currentUser').textContent = `Welcome ${this.currentUser.username}`;
        document.getElementById('userRole').textContent = this.currentUser.role;

        // Show/hide admin features
        if (this.currentUser.role === 'admin') {
            document.getElementById('allTasksNav').style.display = 'block';
            document.getElementById('usersNav').style.display = 'block';
            document.getElementById('addTaskBtn').style.display = 'inline-flex';
            document.getElementById('addUserBtn').style.display = 'inline-flex';
        } else {
            document.getElementById('allTasksNav').style.display = 'none';
            document.getElementById('usersNav').style.display = 'none';
            document.getElementById('addTaskBtn').style.display = 'none';
            document.getElementById('addUserBtn').style.display = 'none';
        }
    }

    async loadInitialData() {
        await Promise.all([
            this.loadTasks(),
            this.loadProjects(),
            this.loadUsers(),
            this.loadNotifications()
        ]);

        this.updateDashboard();
    }

    async loadTasks() {
        try {
            const filter = this.currentUser.role === 'admin' ? '' : 'assigned';
            const response = await fetch(`api/tasks.php?filter=${filter}`);
            const result = await response.json();

            if (result.success) {
                this.tasks = result.data.tasks || [];
                this.updateTaskViews();
            }
        } catch (error) {
            console.error('Error loading tasks:', error);
        }
    }

    async loadUsers() {
        if (this.currentUser.role !== 'admin') return;

        try {
            const response = await fetch('api/users.php');
            const result = await response.json();

            if (result.success) {
                this.users = result.data.users || [];
                this.updateUserSelects();
                this.updateUsersView();
            }
        } catch (error) {
            console.error('Error loading users:', error);
        }
    }

    async loadProjects() {
        try {
            const response = await fetch('api/projects.php');
            const result = await response.json();

            if (result.success) {
                this.projects = result.data.projects || [];
                this.updateProjectSelects();
                this.updateProjectsView();
            }
        } catch (error) {
            console.error('Error loading projects:', error);
        }
    }

    async loadNotifications() {
        try {
            // This would be implemented with a notifications API endpoint
            // For now, we'll use a placeholder
            this.notifications = [];
            this.updateNotificationsView();
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    switchView(view) {
        // Update navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelector(`[data-view="${view}"]`).classList.add('active');

        // Update page title
        const titles = {
            'dashboard': 'Dashboard',
            'my-tasks': 'My Tasks',
            'all-tasks': 'All Tasks',
            'notifications': 'Notifications',
            'users': 'Users',
            'projects': 'Projects'
        };
        document.getElementById('pageTitle').textContent = titles[view];

        // Hide all views
        document.querySelectorAll('.view').forEach(view => {
            view.classList.remove('active');
        });

        // Show selected view
        document.getElementById(view).classList.add('active');
        this.currentView = view;

        // Update view content
        this.updateViewContent();

        // Close mobile sidebar
        if (window.innerWidth <= 768) {
            document.querySelector('.sidebar').classList.remove('open');
        }
    }

    updateViewContent() {
        switch (this.currentView) {
            case 'dashboard':
                this.updateDashboard();
                break;
            case 'my-tasks':
                this.filterMyTasks();
                break;
            case 'all-tasks':
                this.filterAllTasks();
                break;
            case 'notifications':
                this.updateNotificationsView();
                break;
            case 'users':
                this.updateUsersView();
                break;
            case 'projects':
                this.updateProjectsView();
                break;
        }
    }

    updateDashboard() {
        const stats = this.calculateStats();
        document.getElementById('totalTasks').textContent = stats.total;
        document.getElementById('pendingTasks').textContent = stats.pending;
        document.getElementById('inProgressTasks').textContent = stats.inProgress;
        document.getElementById('completedTasks').textContent = stats.completed;

        // Show recent tasks
        const recentTasks = this.tasks.slice(0, 5);
        this.displayTasks('recentTasks', recentTasks);

        // Show overdue tasks
        const overdueTasks = this.tasks.filter(task => this.isOverdue(task));
        this.displayTasks('overdueTasks', overdueTasks);
    }

    calculateStats() {
        const userTasks = this.currentUser.role === 'admin' 
            ? this.tasks 
            : this.tasks.filter(task => task.assigned_to == this.currentUser.user_id);

        const total = userTasks.length;
        const pending = userTasks.filter(task => task.status === 'pending').length;
        const inProgress = userTasks.filter(task => task.status === 'in_progress').length;
        const completed = userTasks.filter(task => task.status === 'completed').length;

        return { total, pending, inProgress, completed };
    }

    updateTaskViews() {
        if (this.currentView === 'my-tasks') {
            this.filterMyTasks();
        } else if (this.currentView === 'all-tasks') {
            this.filterAllTasks();
        }
    }

    filterMyTasks() {
        const status = document.getElementById('myTasksStatusFilter').value;
        const priority = document.getElementById('myTasksPriorityFilter').value;

        let filtered = this.tasks.filter(task => task.assigned_to == this.currentUser.user_id);

        if (status) {
            filtered = filtered.filter(task => task.status === status);
        }

        if (priority) {
            filtered = filtered.filter(task => task.priority === priority);
        }

        this.displayTasks('myTasksList', filtered);
    }

    filterAllTasks() {
        if (this.currentUser.role !== 'admin') return;

        const status = document.getElementById('allTasksStatusFilter').value;
        const priority = document.getElementById('allTasksPriorityFilter').value;
        const assignedTo = document.getElementById('assignedToFilter').value;

        let filtered = [...this.tasks];

        if (status) {
            filtered = filtered.filter(task => task.status === status);
        }

        if (priority) {
            filtered = filtered.filter(task => task.priority === priority);
        }

        if (assignedTo) {
            filtered = filtered.filter(task => task.assigned_to == assignedTo);
        }

        this.displayTasks('allTasksList', filtered);
    }

    displayTasks(containerId, tasks) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (tasks.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-tasks"></i><br>No tasks found</div>';
            return;
        }

        container.innerHTML = tasks.map(task => this.createTaskHTML(task)).join('');
        this.bindTaskEvents(container);
    }

    createTaskHTML(task) {
        const isOverdue = this.isOverdue(task);
        const canEdit = this.currentUser.role === 'admin' || task.assigned_to == this.currentUser.user_id;

        return `
            <div class="task-item" data-task-id="${task.id}">
                <div class="task-header">
                    <div class="task-checkbox ${task.status === 'completed' ? 'completed' : ''}" 
                         data-task-id="${task.id}">
                        ${task.status === 'completed' ? '<i class="fas fa-check"></i>' : ''}
                    </div>
                    <div class="task-title ${task.status === 'completed' ? 'completed' : ''}">
                        ${this.escapeHtml(task.title)}
                    </div>
                    <div class="task-priority ${task.priority}">
                        ${task.priority}
                    </div>
                    ${canEdit ? `
                    <div class="task-actions">
                        <button class="task-action edit-task" data-task-id="${task.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${this.currentUser.role === 'admin' ? `
                        <button class="task-action delete-task" data-task-id="${task.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                        ` : ''}
                    </div>
                    ` : ''}
                </div>
                ${task.description ? `<div class="task-description">${this.escapeHtml(task.description)}</div>` : ''}
                <div class="task-meta">
                    ${task.due_date ? `
                        <div class="task-due-date ${isOverdue ? 'overdue' : ''}">
                            <i class="fas fa-calendar"></i>
                            ${this.formatDate(task.due_date)}
                        </div>
                    ` : ''}
                    ${task.project_name ? `
                        <div class="task-project">
                            <div class="project-color" style="background-color: ${task.project_color || '#667eea'}"></div>
                            ${this.escapeHtml(task.project_name)}
                        </div>
                    ` : ''}
                    <div class="task-assigned-to">
                        <i class="fas fa-user"></i>
                        ${this.escapeHtml(task.assigned_first_name)} ${this.escapeHtml(task.assigned_last_name)}
                    </div>
                    <div class="task-status">
                        <span class="status-badge ${task.status}">
                            ${this.formatStatus(task.status)}
                        </span>
                    </div>
                </div>
            </div>
        `;
    }

    bindTaskEvents(container) {
        // Status toggles
        container.querySelectorAll('.task-checkbox').forEach(checkbox => {
            checkbox.addEventListener('click', (e) => {
                e.stopPropagation();
                const taskId = parseInt(e.currentTarget.dataset.taskId);
                this.toggleTaskStatus(taskId);
            });
        });

        // Edit buttons
        container.querySelectorAll('.edit-task').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const taskId = parseInt(e.currentTarget.dataset.taskId);
                this.editTask(taskId);
            });
        });

        // Delete buttons
        container.querySelectorAll('.delete-task').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const taskId = parseInt(e.currentTarget.dataset.taskId);
                this.deleteTask(taskId);
            });
        });
    }

    async toggleTaskStatus(taskId) {
        const task = this.tasks.find(t => t.id === taskId);
        if (!task) return;

        let newStatus;
        if (task.status === 'pending') {
            newStatus = 'in_progress';
        } else if (task.status === 'in_progress') {
            newStatus = 'completed';
        } else {
            newStatus = 'pending';
        }

        try {
            const response = await fetch('api/tasks.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    task_id: taskId,
                    status: newStatus
                })
            });

            const result = await response.json();

            if (result.success) {
                task.status = newStatus;
                this.updateTaskViews();
                this.updateDashboard();
                this.showToast(`Task marked as ${this.formatStatus(newStatus)}`, 'success');
            } else {
                this.showToast(result.message || 'Error updating task', 'error');
            }
        } catch (error) {
            this.showToast('Error updating task', 'error');
            console.error('Error:', error);
        }
    }

    // Modal functions
    openTaskModal(task = null) {
        if (this.currentUser.role !== 'admin') return;

        const modal = document.getElementById('taskModal');
        const form = document.getElementById('taskForm');
        
        if (task) {
            document.getElementById('taskModalTitle').textContent = 'Edit Task';
            document.getElementById('taskId').value = task.id;
            document.getElementById('taskTitle').value = task.title;
            document.getElementById('taskDescription').value = task.description || '';
            document.getElementById('taskPriority').value = task.priority;
            document.getElementById('taskAssignedTo').value = task.assigned_to || '';
            document.getElementById('taskDueDate').value = task.due_date || '';
            document.getElementById('taskDueTime').value = task.due_time || '';
            document.getElementById('taskProject').value = task.project_id || '';
            document.getElementById('taskTags').value = task.tags || '';
        } else {
            document.getElementById('taskModalTitle').textContent = 'Add New Task';
            form.reset();
            document.getElementById('taskId').value = '';
        }

        modal.classList.add('active');
    }

    closeTaskModal() {
        document.getElementById('taskModal').classList.remove('active');
    }

    async saveTask() {
        const formData = new FormData(document.getElementById('taskForm'));
        const taskData = Object.fromEntries(formData.entries());
        
        this.showLoading();

        try {
            const url = 'api/tasks.php';
            const method = taskData.taskId ? 'PUT' : 'POST';
            
            if (taskData.taskId) {
                taskData.task_id = taskData.taskId;
                delete taskData.taskId;
            }

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(taskData)
            });

            const result = await response.json();

            if (result.success) {
                this.showToast(taskData.task_id ? 'Task updated successfully' : 'Task created successfully', 'success');
                this.closeTaskModal();
                this.loadTasks();
            } else {
                this.showToast(result.message || 'Error saving task', 'error');
            }
        } catch (error) {
            this.showToast('Error saving task', 'error');
            console.error('Error:', error);
        }

        this.hideLoading();
    }

    editTask(taskId) {
        const task = this.tasks.find(t => t.id === taskId);
        if (task) {
            this.openTaskModal(task);
        }
    }

    async deleteTask(taskId) {
        if (!confirm('Are you sure you want to delete this task?')) {
            return;
        }

        try {
            const response = await fetch(`api/tasks.php?id=${taskId}`, {
                method: 'DELETE'
            });

            const result = await response.json();

            if (result.success) {
                this.tasks = this.tasks.filter(t => t.id !== taskId);
                this.updateTaskViews();
                this.updateDashboard();
                this.showToast('Task deleted successfully', 'success');
            } else {
                this.showToast(result.message || 'Error deleting task', 'error');
            }
        } catch (error) {
            this.showToast('Error deleting task', 'error');
            console.error('Error:', error);
        }
    }

    // User management (admin only)
    openUserModal(user = null) {
        if (this.currentUser.role !== 'admin') return;

        const modal = document.getElementById('userModal');
        const form = document.getElementById('userForm');
        
        if (user) {
            document.getElementById('userModalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = user.id;
            document.getElementById('firstName').value = user.first_name;
            document.getElementById('lastName').value = user.last_name;
            document.getElementById('userUsername').value = user.username;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userPassword').required = false;
            document.getElementById('userRole').value = user.role;
            document.getElementById('userStatus').value = user.status;
        } else {
            document.getElementById('userModalTitle').textContent = 'Add New User';
            form.reset();
            document.getElementById('userId').value = '';
            document.getElementById('userPassword').required = true;
        }

        modal.classList.add('active');
    }

    closeUserModal() {
        document.getElementById('userModal').classList.remove('active');
    }

    async saveUser() {
        const formData = new FormData(document.getElementById('userForm'));
        const userData = Object.fromEntries(formData.entries());
        
        this.showLoading();

        try {
            const url = 'api/users.php';
            const method = userData.userId ? 'PUT' : 'POST';
            
            if (userData.userId) {
                userData.user_id = userData.userId;
                delete userData.userId;
            }

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            });

            const result = await response.json();

            if (result.success) {
                this.showToast(userData.user_id ? 'User updated successfully' : 'User created successfully', 'success');
                this.closeUserModal();
                this.loadUsers();
            } else {
                this.showToast(result.message || 'Error saving user', 'error');
            }
        } catch (error) {
            this.showToast('Error saving user', 'error');
            console.error('Error:', error);
        }

        this.hideLoading();
    }

    updateUsersView() {
        if (this.currentUser.role !== 'admin') return;

        const container = document.getElementById('usersList');
        if (!container) return;

        if (this.users.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-users"></i><br>No users found</div>';
            return;
        }

        container.innerHTML = this.users.map(user => `
            <div class="user-card">
                <div class="user-info">
                    <h3>${this.escapeHtml(user.first_name)} ${this.escapeHtml(user.last_name)}</h3>
                    <p>@${this.escapeHtml(user.username)} - ${this.escapeHtml(user.email)}</p>
                    <div class="user-meta">
                        <span class="role-badge ${user.role}">${user.role}</span>
                        <span class="status-badge ${user.status}">${user.status}</span>
                        <span class="task-count">${user.total_tasks || 0} tasks</span>
                    </div>
                </div>
                <div class="user-actions">
                    <button class="btn-secondary edit-user" data-user-id="${user.id}">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>
            </div>
        `).join('');

        // Bind user events
        container.querySelectorAll('.edit-user').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const userId = parseInt(e.currentTarget.dataset.userId);
                const user = this.users.find(u => u.id === userId);
                if (user) this.openUserModal(user);
            });
        });
    }

    updateUserSelects() {
        const selects = ['taskAssignedTo', 'assignedToFilter'];
        
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (!select) return;

            const currentValue = select.value;
            select.innerHTML = selectId === 'assignedToFilter' 
                ? '<option value="">All Users</option>' 
                : '<option value="">Select User</option>';

            this.users.filter(user => user.status === 'active').forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = `${user.first_name} ${user.last_name}`;
                select.appendChild(option);
            });

            select.value = currentValue;
        });
    }

    // Project management
    openProjectModal(project = null) {
        const modal = document.getElementById('projectModal');
        const form = document.getElementById('projectForm');
        
        if (project) {
            document.getElementById('projectName').value = project.name;
            document.getElementById('projectDescription').value = project.description || '';
            document.getElementById('projectColor').value = project.color;
        } else {
            form.reset();
        }

        modal.classList.add('active');
    }

    closeProjectModal() {
        document.getElementById('projectModal').classList.remove('active');
    }

    async saveProject() {
        const formData = new FormData(document.getElementById('projectForm'));
        const projectData = Object.fromEntries(formData.entries());
        
        this.showLoading();

        try {
            const response = await fetch('api/projects.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(projectData)
            });

            const result = await response.json();

            if (result.success) {
                this.showToast('Project created successfully', 'success');
                this.closeProjectModal();
                this.loadProjects();
            } else {
                this.showToast(result.message || 'Error saving project', 'error');
            }
        } catch (error) {
            this.showToast('Error saving project', 'error');
            console.error('Error:', error);
        }

        this.hideLoading();
    }

    updateProjectsView() {
        const container = document.getElementById('projectsList');
        if (!container) return;

        if (this.projects.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-project-diagram"></i><br>No projects found</div>';
            return;
        }

        container.innerHTML = this.projects.map(project => `
            <div class="project-card" style="border-left-color: ${project.color}">
                <div class="project-info">
                    <h3 style="color: ${project.color}">${this.escapeHtml(project.name)}</h3>
                    <p>${this.escapeHtml(project.description || 'No description')}</p>
                    <div class="project-meta">
                        <span class="task-count">${project.task_count || 0} tasks</span>
                        ${project.completed_tasks ? `<span class="completion-rate">${Math.round((project.completed_tasks / project.task_count) * 100)}% complete</span>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    updateProjectSelects() {
        const select = document.getElementById('taskProject');
        if (!select) return;

        const currentValue = select.value;
        select.innerHTML = '<option value="">No Project</option>';

        this.projects.forEach(project => {
            const option = document.createElement('option');
            option.value = project.id;
            option.textContent = project.name;
            select.appendChild(option);
        });

        select.value = currentValue;
    }

    updateNotificationsView() {
        const container = document.getElementById('notificationsList');
        if (!container) return;

        if (this.notifications.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-bell"></i><br>No notifications</div>';
            return;
        }

        // This would display actual notifications
        container.innerHTML = this.notifications.map(notification => `
            <div class="notification-item ${notification.is_read ? 'read' : 'unread'}">
                <div class="notification-content">
                    <h4>${this.escapeHtml(notification.message)}</h4>
                    <p>${this.formatDate(notification.created_at)}</p>
                </div>
            </div>
        `).join('');
    }

    searchTasks(searchTerm) {
        // This would implement search functionality
        console.log('Searching for:', searchTerm);
    }

    // Utility functions
    isOverdue(task) {
        if (!task.due_date || task.status === 'completed') return false;
        const today = new Date().toISOString().split('T')[0];
        return task.due_date < today;
    }

    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const today = new Date();
        const tomorrow = new Date(today.getTime() + 24 * 60 * 60 * 1000);

        const dateStr = date.toISOString().split('T')[0];
        const todayStr = today.toISOString().split('T')[0];
        const tomorrowStr = tomorrow.toISOString().split('T')[0];

        if (dateStr === todayStr) return 'Today';
        if (dateStr === tomorrowStr) return 'Tomorrow';

        return date.toLocaleDateString();
    }

    formatStatus(status) {
        return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showLoading() {
        document.getElementById('loading').style.display = 'flex';
    }

    hideLoading() {
        document.getElementById('loading').style.display = 'none';
    }

    showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;

        container.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new TaskFlowApp();
});