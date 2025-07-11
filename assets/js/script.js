class TaskManager {
    constructor() {
        this.currentView = 'dashboard';
        this.tasks = [];
        this.projects = [];
        this.filteredTasks = [];
        this.searchTerm = '';
        this.filters = {
            status: '',
            priority: '',
            project: ''
        };
        this.sortBy = 'created_desc';
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadProjects();
        this.loadTasks();
        this.updateUI();
    }

    bindEvents() {
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

        // Add task button
        document.getElementById('addTaskBtn').addEventListener('click', () => {
            this.openTaskModal();
        });

        // Add project button
        document.getElementById('addProjectBtn').addEventListener('click', () => {
            this.openProjectModal();
        });

        // Task form submission
        document.getElementById('taskForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveTask();
        });

        // Project form submission
        document.getElementById('projectForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveProject();
        });

        // Modal close buttons
        document.getElementById('closeModal').addEventListener('click', () => {
            this.closeTaskModal();
        });

        document.getElementById('cancelTask').addEventListener('click', () => {
            this.closeTaskModal();
        });

        document.getElementById('closeProjectModal').addEventListener('click', () => {
            this.closeProjectModal();
        });

        document.getElementById('cancelProject').addEventListener('click', () => {
            this.closeProjectModal();
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', (e) => {
            this.searchTerm = e.target.value.toLowerCase();
            this.filterAndDisplayTasks();
        });

        // Filter controls
        document.getElementById('statusFilter').addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.filterAndDisplayTasks();
        });

        document.getElementById('priorityFilter').addEventListener('change', (e) => {
            this.filters.priority = e.target.value;
            this.filterAndDisplayTasks();
        });

        document.getElementById('projectFilter').addEventListener('change', (e) => {
            this.filters.project = e.target.value;
            this.filterAndDisplayTasks();
        });

        document.getElementById('sortBy').addEventListener('change', (e) => {
            this.sortBy = e.target.value;
            this.filterAndDisplayTasks();
        });

        // Close modals when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                if (e.target.id === 'taskModal') {
                    this.closeTaskModal();
                } else if (e.target.id === 'projectModal') {
                    this.closeProjectModal();
                }
            }
        });

        // Close sidebar when clicking on main content (mobile)
        document.querySelector('.main-content').addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                document.querySelector('.sidebar').classList.remove('open');
            }
        });
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
            'all-tasks': 'All Tasks',
            'today': 'Today',
            'upcoming': 'Upcoming',
            'completed': 'Completed'
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
            case 'all-tasks':
                this.displayTasks('allTasksList', this.filteredTasks);
                break;
            case 'today':
                const todayTasks = this.getTodayTasks();
                this.displayTasks('todayTasksList', todayTasks);
                break;
            case 'upcoming':
                const upcomingTasks = this.getUpcomingTasks();
                this.displayTasks('upcomingTasksList', upcomingTasks);
                break;
            case 'completed':
                const completedTasks = this.tasks.filter(task => task.status === 'completed');
                this.displayTasks('completedTasksList', completedTasks);
                break;
        }
    }

    updateDashboard() {
        // Update statistics
        const stats = this.calculateStats();
        document.getElementById('totalTasks').textContent = stats.total;
        document.getElementById('completedTasks').textContent = stats.completed;
        document.getElementById('pendingTasks').textContent = stats.pending;
        document.getElementById('overdueTasks').textContent = stats.overdue;

        // Update today's tasks
        const todayTasks = this.getTodayTasks().slice(0, 5);
        this.displayTasks('todayTasks', todayTasks);

        // Update upcoming tasks
        const upcomingTasks = this.getUpcomingTasks().slice(0, 5);
        this.displayTasks('upcomingTasks', upcomingTasks);
    }

    calculateStats() {
        const total = this.tasks.length;
        const completed = this.tasks.filter(task => task.status === 'completed').length;
        const pending = this.tasks.filter(task => task.status === 'pending').length;
        const overdue = this.tasks.filter(task => this.isOverdue(task)).length;

        return { total, completed, pending, overdue };
    }

    getTodayTasks() {
        const today = new Date().toISOString().split('T')[0];
        return this.tasks.filter(task => {
            return task.due_date === today || 
                   (task.status === 'pending' && !task.due_date);
        });
    }

    getUpcomingTasks() {
        const today = new Date();
        const nextWeek = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
        
        return this.tasks.filter(task => {
            if (!task.due_date) return false;
            const dueDate = new Date(task.due_date);
            return dueDate > today && dueDate <= nextWeek && task.status === 'pending';
        }).sort((a, b) => new Date(a.due_date) - new Date(b.due_date));
    }

    isOverdue(task) {
        if (!task.due_date || task.status === 'completed') return false;
        const today = new Date().toISOString().split('T')[0];
        return task.due_date < today;
    }

    displayTasks(containerId, tasks) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (tasks.length === 0) {
            container.innerHTML = '<div class="empty-state">No tasks found</div>';
            return;
        }

        container.innerHTML = tasks.map(task => this.createTaskHTML(task)).join('');

        // Bind task events
        this.bindTaskEvents(container);
    }

    createTaskHTML(task) {
        const project = this.projects.find(p => p.id === task.project_id);
        const isOverdue = this.isOverdue(task);
        const tags = task.tags ? task.tags.split(',').map(tag => tag.trim()) : [];

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
                    <div class="task-actions">
                        <button class="task-action edit-task" data-task-id="${task.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="task-action delete-task" data-task-id="${task.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                ${task.description ? `<div class="task-description">${this.escapeHtml(task.description)}</div>` : ''}
                <div class="task-meta">
                    ${task.due_date ? `
                        <div class="task-due-date ${isOverdue ? 'overdue' : ''}">
                            <i class="fas fa-calendar"></i>
                            ${this.formatDate(task.due_date)}
                        </div>
                    ` : ''}
                    ${project ? `
                        <div class="task-project">
                            <div class="project-color" style="background-color: ${project.color}"></div>
                            ${this.escapeHtml(project.name)}
                        </div>
                    ` : ''}
                    ${tags.length > 0 ? `
                        <div class="task-tags">
                            ${tags.map(tag => `<span class="task-tag">${this.escapeHtml(tag)}</span>`).join('')}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    bindTaskEvents(container) {
        // Checkbox toggles
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

    filterAndDisplayTasks() {
        this.filteredTasks = this.tasks.filter(task => {
            // Search filter
            if (this.searchTerm) {
                const searchInTitle = task.title.toLowerCase().includes(this.searchTerm);
                const searchInDescription = task.description && task.description.toLowerCase().includes(this.searchTerm);
                const searchInTags = task.tags && task.tags.toLowerCase().includes(this.searchTerm);
                if (!searchInTitle && !searchInDescription && !searchInTags) {
                    return false;
                }
            }

            // Status filter
            if (this.filters.status && task.status !== this.filters.status) {
                return false;
            }

            // Priority filter
            if (this.filters.priority && task.priority !== this.filters.priority) {
                return false;
            }

            // Project filter
            if (this.filters.project) {
                if (this.filters.project === 'none' && task.project_id) {
                    return false;
                } else if (this.filters.project !== 'none' && task.project_id != this.filters.project) {
                    return false;
                }
            }

            return true;
        });

        // Sort tasks
        this.sortTasks();

        // Update current view
        this.updateViewContent();
    }

    sortTasks() {
        this.filteredTasks.sort((a, b) => {
            switch (this.sortBy) {
                case 'created_desc':
                    return new Date(b.created_at) - new Date(a.created_at);
                case 'created_asc':
                    return new Date(a.created_at) - new Date(b.created_at);
                case 'due_asc':
                    if (!a.due_date && !b.due_date) return 0;
                    if (!a.due_date) return 1;
                    if (!b.due_date) return -1;
                    return new Date(a.due_date) - new Date(b.due_date);
                case 'due_desc':
                    if (!a.due_date && !b.due_date) return 0;
                    if (!a.due_date) return 1;
                    if (!b.due_date) return -1;
                    return new Date(b.due_date) - new Date(a.due_date);
                case 'priority_desc':
                    const priorityOrder = { high: 3, medium: 2, low: 1 };
                    return priorityOrder[b.priority] - priorityOrder[a.priority];
                case 'priority_asc':
                    const priorityOrderAsc = { high: 3, medium: 2, low: 1 };
                    return priorityOrderAsc[a.priority] - priorityOrderAsc[b.priority];
                default:
                    return 0;
            }
        });
    }

    // Modal functions
    openTaskModal(task = null) {
        const modal = document.getElementById('taskModal');
        const form = document.getElementById('taskForm');
        
        if (task) {
            // Editing existing task
            document.getElementById('modalTitle').textContent = 'Edit Task';
            document.getElementById('taskId').value = task.id;
            document.getElementById('taskTitle').value = task.title;
            document.getElementById('taskDescription').value = task.description || '';
            document.getElementById('taskPriority').value = task.priority;
            document.getElementById('taskProject').value = task.project_id || '';
            document.getElementById('taskDueDate').value = task.due_date || '';
            document.getElementById('taskDueTime').value = task.due_time || '';
            document.getElementById('taskTags').value = task.tags || '';
        } else {
            // Creating new task
            document.getElementById('modalTitle').textContent = 'Add New Task';
            form.reset();
            document.getElementById('taskId').value = '';
        }

        // Update project options
        this.updateProjectOptions();

        modal.classList.add('active');
    }

    closeTaskModal() {
        document.getElementById('taskModal').classList.remove('active');
    }

    openProjectModal() {
        const modal = document.getElementById('projectModal');
        document.getElementById('projectForm').reset();
        modal.classList.add('active');
    }

    closeProjectModal() {
        document.getElementById('projectModal').classList.remove('active');
    }

    updateProjectOptions() {
        const projectSelect = document.getElementById('taskProject');
        const filterSelect = document.getElementById('projectFilter');

        // Update task form project options
        projectSelect.innerHTML = '<option value="">No Project</option>';
        this.projects.forEach(project => {
            projectSelect.innerHTML += `<option value="${project.id}">${this.escapeHtml(project.name)}</option>`;
        });

        // Update filter project options
        filterSelect.innerHTML = '<option value="">All Projects</option>';
        filterSelect.innerHTML += '<option value="none">No Project</option>';
        this.projects.forEach(project => {
            filterSelect.innerHTML += `<option value="${project.id}">${this.escapeHtml(project.name)}</option>`;
        });
    }

    // CRUD operations
    async saveTask() {
        const formData = new FormData(document.getElementById('taskForm'));
        const taskData = Object.fromEntries(formData.entries());
        
        this.showLoading();

        try {
            const url = taskData.taskId ? 'api/tasks.php' : 'api/tasks.php';
            const method = taskData.taskId ? 'PUT' : 'POST';
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(taskData)
            });

            const result = await response.json();

            if (result.success) {
                this.showToast(taskData.taskId ? 'Task updated successfully' : 'Task created successfully', 'success');
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

    async loadTasks() {
        try {
            const response = await fetch('api/tasks.php');
            const result = await response.json();

            if (result.success) {
                this.tasks = result.data.tasks || result.data;
                this.filteredTasks = [...this.tasks];
                this.filterAndDisplayTasks();
            }
        } catch (error) {
            console.error('Error loading tasks:', error);
            // Use sample data for demo
            this.loadSampleTasks();
        }
    }

    async loadProjects() {
        try {
            const response = await fetch('api/projects.php');
            const result = await response.json();

            if (result.success) {
                this.projects = result.data.projects || result.data;
                this.updateProjectsList();
                this.updateProjectOptions();
            }
        } catch (error) {
            console.error('Error loading projects:', error);
            // Use sample data for demo
            this.loadSampleProjects();
        }
    }

    loadSampleTasks() {
        this.tasks = [
            {
                id: 1,
                title: 'Complete project proposal',
                description: 'Finish the quarterly project proposal for the management team',
                status: 'pending',
                priority: 'high',
                due_date: '2025-01-15',
                due_time: '17:00',
                project_id: 1,
                tags: 'work, urgent',
                created_at: '2025-01-10'
            },
            {
                id: 2,
                title: 'Review code changes',
                description: 'Review and approve the latest code changes from the development team',
                status: 'pending',
                priority: 'medium',
                due_date: '2025-01-14',
                project_id: 1,
                tags: 'development, review',
                created_at: '2025-01-10'
            },
            {
                id: 3,
                title: 'Buy groceries',
                description: 'Weekly grocery shopping',
                status: 'completed',
                priority: 'low',
                due_date: '2025-01-13',
                project_id: null,
                tags: 'personal',
                created_at: '2025-01-09'
            },
            {
                id: 4,
                title: 'Team meeting preparation',
                description: 'Prepare agenda and materials for the weekly team meeting',
                status: 'pending',
                priority: 'medium',
                due_date: '2025-01-16',
                project_id: 1,
                tags: 'work, meeting',
                created_at: '2025-01-11'
            }
        ];

        this.filteredTasks = [...this.tasks];
        this.filterAndDisplayTasks();
    }

    loadSampleProjects() {
        this.projects = [
            {
                id: 1,
                name: 'Work Projects',
                color: '#667eea'
            },
            {
                id: 2,
                name: 'Personal',
                color: '#48bb78'
            }
        ];

        this.updateProjectsList();
        this.updateProjectOptions();
    }

    updateProjectsList() {
        const container = document.getElementById('projectsList');
        container.innerHTML = this.projects.map(project => `
            <li class="project-item" data-project-id="${project.id}">
                <div class="project-color" style="background-color: ${project.color}"></div>
                <span>${this.escapeHtml(project.name)}</span>
            </li>
        `).join('');

        // Bind project click events
        container.querySelectorAll('.project-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const projectId = e.currentTarget.dataset.projectId;
                this.filterByProject(projectId);
            });
        });
    }

    filterByProject(projectId) {
        this.filters.project = projectId;
        document.getElementById('projectFilter').value = projectId;
        this.switchView('all-tasks');
        this.filterAndDisplayTasks();
    }

    async toggleTaskStatus(taskId) {
        const task = this.tasks.find(t => t.id === taskId);
        if (!task) return;

        const newStatus = task.status === 'completed' ? 'pending' : 'completed';

        try {
            const response = await fetch('api/tasks.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    taskId: taskId,
                    status: newStatus
                })
            });

            const result = await response.json();

            if (result.success) {
                task.status = newStatus;
                this.filterAndDisplayTasks();
                this.showToast(`Task marked as ${newStatus}`, 'success');
            }
        } catch (error) {
            // For demo purposes, update locally
            task.status = newStatus;
            this.filterAndDisplayTasks();
            this.showToast(`Task marked as ${newStatus}`, 'success');
        }
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
                this.filterAndDisplayTasks();
                this.showToast('Task deleted successfully', 'success');
            }
        } catch (error) {
            // For demo purposes, delete locally
            this.tasks = this.tasks.filter(t => t.id !== taskId);
            this.filterAndDisplayTasks();
            this.showToast('Task deleted successfully', 'success');
        }
    }

    // Utility functions
    formatDate(dateString) {
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

    escapeHtml(text) {
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

        // Auto remove after 3 seconds
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    updateUI() {
        this.updateViewContent();
    }
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new TaskManager();
});