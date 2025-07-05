// File: js/script.js
document.addEventListener('DOMContentLoaded', () => {
    // API Endpoints
    const PROJECTS_API = 'php/projects_api.php';
    const TASKS_API = 'php/tasks_api.php';
    const USERS_API = 'php/users_api.php';

    // DOM Elements - General
    const notificationsDiv = document.getElementById('notifications');
    const mainHeaderH1 = document.querySelector('header h1');

    // DOM Elements - Dashboard
    const dashboardSection = document.getElementById('dashboard-section');
    const totalProjectsCountEl = document.getElementById('totalProjectsCount');
    const projectTilesContainer = document.getElementById('projectTilesContainer');
    const showCreateProjectFormBtnDashboard = document.getElementById('showCreateProjectFormBtnDashboard');
    const projectFormContainerDashboard = document.getElementById('projectFormContainerDashboard');
    const cancelProjectFormBtnDashboard = document.getElementById('cancelProjectFormBtnDashboard');
    
    // DOM Elements - Project Form (shared by dashboard)
    const projectForm = document.getElementById('projectForm');
    const projectOwnerSelect = document.getElementById('projectOwner');
    
    // DOM Elements - Tasks Section
    const tasksSection = document.getElementById('tasks-section');
    const selectedProjectNameH2 = document.getElementById('selectedProjectName');
    const showCreateTaskFormBtnTopRight = document.getElementById('showCreateTaskFormBtnTopRight');
    const backToDashboardBtn = document.getElementById('backToDashboardBtn');
    const taskFormContainer = document.getElementById('taskFormContainer');
    const taskForm = document.getElementById('taskForm');
    const cancelTaskFormBtn = document.getElementById('cancelTaskFormBtn');
    const taskAssigneeSelect = document.getElementById('taskAssignee');
    const taskProjectIdInput = document.getElementById('taskProjectId');
    const taskDependencySelect = document.getElementById('taskDependency');
    const taskStartDateInput = document.getElementById('taskStartDate'); // Added for the new field


    // DOM Elements - Task Views
    const showTaskListViewBtn = document.getElementById('showTaskListViewBtn');
    const showKanbanViewBtn = document.getElementById('showKanbanViewBtn');
    const showGanttViewBtn = document.getElementById('showGanttViewBtn');
    const taskListView = document.getElementById('taskListView');
    const tasksListDiv = document.getElementById('tasksList');
    const kanbanView = document.getElementById('kanbanView'); 
    const ganttChartContainer = document.getElementById('ganttChartContainer');

    // Global State
    let currentProjectId = null;
    let allUsers = [];
    let projectTasks = []; 
    let allProjectsData = [];

    // --- Utility Functions ---
    function showNotification(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notificationsDiv.appendChild(notification);
        void notification.offsetWidth; 
        notification.classList.add('show');
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => { if(notificationsDiv.contains(notification)) notificationsDiv.removeChild(notification); }, 300);
        }, duration);
    }
    
    async function fetchData(url, options = {}) {
        try {
            const response = await fetch(url, options);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: `HTTP error! status: ${response.status}` }));
                throw new Error(errorData.message || `Request failed with status ${response.status}`);
            }
            return response.json();
        } catch (error) {
            console.error('Fetch error:', error);
            showNotification(`Error: ${error.message}`, 'error');
            throw error;
        }
    }

    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, match => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[match]);
    }

    // --- User Functions ---
    async function loadUsers() {
        try {
            const data = await fetchData(`${USERS_API}?action=getUsers`);
            if (data.success && data.users) {
                allUsers = data.users;
                populateUserSelect(projectOwnerSelect, allUsers);
                populateUserSelect(taskAssigneeSelect, allUsers, true);
            } else { showNotification(data.message || 'Failed to load users.', 'error'); }
        } catch (error) { /* Handled by fetchData */ }
    }

    function populateUserSelect(selectElement, users, addUnassigned = false) {
        selectElement.innerHTML = '';
        if (addUnassigned) {
            const unassignedOption = document.createElement('option');
            unassignedOption.value = ""; unassignedOption.textContent = "Unassigned";
            selectElement.appendChild(unassignedOption);
        }
        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.user_id; option.textContent = user.user_name;
            selectElement.appendChild(option);
        });
    }

    // --- Dashboard and Project Handling ---
    function showDashboard() {
        dashboardSection.style.display = 'block';
        tasksSection.style.display = 'none';
        projectFormContainerDashboard.style.display = 'none';
        mainHeaderH1.textContent = 'Project Management Tool';
        currentProjectId = null; 
        loadProjectsForDashboard();
    }
    
    async function loadProjectsForDashboard() {
        try {
            const data = await fetchData(`${PROJECTS_API}?action=getProjects`);
            if (data.success && data.projects) {
                allProjectsData = data.projects; 
                totalProjectsCountEl.textContent = data.projects.length;
                renderProjectTiles(data.projects);
            } else {
                showNotification(data.message || 'Failed to load projects.', 'error');
                projectTilesContainer.innerHTML = '<p>Could not load projects.</p>';
                totalProjectsCountEl.textContent = '0';
            }
        } catch (error) {
            projectTilesContainer.innerHTML = '<p>Error loading projects.</p>';
            totalProjectsCountEl.textContent = 'Error';
        }
    }

    function renderProjectTiles(projects) {
        projectTilesContainer.innerHTML = '';
        if (projects.length === 0) {
            projectTilesContainer.innerHTML = '<p>No projects found. Create one to get started!</p>';
            return;
        }
        projects.forEach(project => {
            const tile = document.createElement('div');
            tile.className = `project-tile ${escapeHTML(project.project_status_class || 'tile-active')}`;
            tile.dataset.projectId = project.project_id;
            
            const statusBadge = project.project_status_text ? 
                `<div class="project-status-badge ${escapeHTML(project.project_status_class || '')}">${escapeHTML(project.project_status_text)}</div>` : 
                '';

            tile.innerHTML = `
                ${statusBadge} 
                <h4>${escapeHTML(project.project_name)}</h4>
                <p class="description">${escapeHTML(project.project_description || 'No description available.')}</p>
                <div class="details-row">
                    <span class="dates"><strong>Start:</strong> ${project.start_date || 'N/A'} | <strong>End:</strong> ${project.end_date || 'N/A'}</span>
                    <span class="owner"><small><strong>Owner:</strong> ${escapeHTML(project.owner_name || 'N/A')}</small></span>
                </div>
                <div class="actions">
                    <button class="edit-project-tile-btn" data-project-id="${project.project_id}">Edit</button>
                    <button class="delete-project-tile-btn" data-project-id="${project.project_id}">Delete</button>
                </div>
            `;
            
            tile.addEventListener('click', (e) => {
                if (e.target.closest('.actions button') || e.target.closest('.project-status-badge')) {
                    return;
                }
                currentProjectId = project.project_id;
                dashboardSection.style.display = 'none';
                tasksSection.style.display = 'block';
                selectedProjectNameH2.textContent = `${escapeHTML(project.project_name)}`;
                mainHeaderH1.textContent = escapeHTML(project.project_name);
                loadTasksForProject(currentProjectId);
                switchTaskView('kanban');
            });
            projectTilesContainer.appendChild(tile);
        });

        projectTilesContainer.querySelectorAll('.edit-project-tile-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const projectId = btn.dataset.projectId;
                const projectData = allProjectsData.find(p => p.project_id == projectId);
                if (projectData) editProjectFromDashboard(projectData);
            });
        });
        projectTilesContainer.querySelectorAll('.delete-project-tile-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                deleteProject(btn.dataset.projectId);
            });
        });
    }
    
    showCreateProjectFormBtnDashboard.addEventListener('click', () => {
        projectForm.reset();
        projectForm.querySelector('#projectId').value = '';
        projectFormContainerDashboard.style.display = 'block';
    });

    cancelProjectFormBtnDashboard.addEventListener('click', () => {
        projectFormContainerDashboard.style.display = 'none';
    });
    
    function editProjectFromDashboard(project) {
        if (project) {
            projectForm.reset();
            projectForm.querySelector('#projectId').value = project.project_id;
            projectForm.querySelector('#projectName').value = project.project_name;
            projectForm.querySelector('#projectDescription').value = project.project_description || '';
            projectForm.querySelector('#projectStartDate').value = project.start_date || '';
            projectForm.querySelector('#projectEndDate').value = project.end_date || '';
            projectOwnerSelect.value = project.project_owner_id || '';
            projectFormContainerDashboard.style.display = 'block';
            projectFormContainerDashboard.scrollIntoView({ behavior: 'smooth' });
        }
    }

    projectForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(projectForm);
        const projectIdVal = formData.get('projectId');
        const action = projectIdVal ? 'updateProject' : 'createProject';
        formData.append('action', action);
        try {
            const data = await fetchData(PROJECTS_API, { method: 'POST', body: formData });
            if (data.success) {
                showNotification(data.message, 'success');
                loadProjectsForDashboard();
                projectFormContainerDashboard.style.display = 'none';
            } else { showNotification(data.message || 'Failed to save project.', 'error'); }
        } catch (error) { /* Handled by fetchData */ }
    });

    async function deleteProject(projectIdToDelete) {
        if (!confirm('Are you sure you want to delete this project and all its tasks?')) return;
        try {
            const formData = new FormData();
            formData.append('action', 'deleteProject');
            formData.append('project_id', projectIdToDelete);
            const data = await fetchData(PROJECTS_API, { method: 'POST', body: formData });
            if (data.success) {
                showNotification(data.message, 'success');
                loadProjectsForDashboard();
                if (tasksSection.style.display === 'block' && currentProjectId == projectIdToDelete) {
                    showDashboard(); 
                }
            } else { showNotification(data.message || 'Failed to delete project.', 'error'); }
        } catch (error) { /* Handled by fetchData */ }
    }

    // --- Task Handling ---
    showCreateTaskFormBtnTopRight.addEventListener('click', () => {
        if (!currentProjectId) {
            showNotification('Error: No project selected for task.', 'error'); return;
        }
        taskForm.reset();
        taskForm.querySelector('#taskId').value = ''; 
        taskProjectIdInput.value = currentProjectId; 
        populateTaskDependencyDropdown(null);
        taskFormContainer.style.display = 'block';
        taskFormContainer.scrollIntoView({behavior: 'smooth'});
    });

    if (backToDashboardBtn) {
        backToDashboardBtn.addEventListener('click', () => {
            showDashboard();
        });
    }

    cancelTaskFormBtn.addEventListener('click', () => {
        taskFormContainer.style.display = 'none';
    });

    taskForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(taskForm); 
        const taskId = formData.get('task_id');
        formData.append('action', taskId ? 'updateTask' : 'createTask');
        try {
            const data = await fetchData(TASKS_API, { method: 'POST', body: formData });
            if (data.success) {
                showNotification(data.message, 'success');
                loadTasksForProject(currentProjectId);
                taskFormContainer.style.display = 'none';
            } else { showNotification(data.message || 'Failed to save task.', 'error'); }
        } catch (error) { /* Handled by fetchData */ }
    });
    
    async function loadTasksForProject(projectIdToLoad) {
        if (!projectIdToLoad) return;
        taskFormContainer.style.display = 'none';
        try {
            const data = await fetchData(`${TASKS_API}?action=getTasksByProject&project_id=${projectIdToLoad}`);
            if (data.success && data.tasks) {
                projectTasks = data.tasks;
                const activeViewButton = document.querySelector('.view-toggle button.active-view');
                let viewToRender = 'kanban';
                if (activeViewButton) {
                    if (activeViewButton.id === 'showTaskListViewBtn') viewToRender = 'list';
                    else if (activeViewButton.id === 'showGanttViewBtn') viewToRender = 'gantt';
                }
                
                if (viewToRender === 'list') renderTasksList(projectTasks);
                else if (viewToRender === 'kanban') renderKanbanBoard(projectTasks);
                else if (viewToRender === 'gantt') renderGanttChart(projectTasks, projectIdToLoad);

            } else {
                showNotification(data.message || 'Failed to load tasks.', 'error');
                tasksListDiv.innerHTML = '<p>Could not load tasks.</p>'; clearKanbanColumns(); ganttChartContainer.innerHTML = '<p>Could not load tasks for Gantt.</p>';
                projectTasks = [];
            }
        } catch (error) { projectTasks = []; }
    }

    function populateTaskDependencyDropdown(currentEditingTaskId = null) {
        taskDependencySelect.innerHTML = '<option value="">-- None --</option>';
        if (projectTasks && projectTasks.length > 0) {
            projectTasks.forEach(task => {
                if (task.task_id && task.task_id != currentEditingTaskId) {
                    const option = document.createElement('option');
                    option.value = task.task_id;
                    option.textContent = escapeHTML(task.task_name);
                    taskDependencySelect.appendChild(option);
                }
            });
        }
    }

    function renderTasksList(tasks) {
        tasksListDiv.innerHTML = '';
        if (tasks.length === 0) { tasksListDiv.innerHTML = '<p>No tasks for this project yet. Add one!</p>'; return; }
        tasks.forEach(task => {
            const taskDiv = document.createElement('div');
            taskDiv.className = 'task-item';
            let dependencyInfo = '';
            if (task.dependent_on_task_id && task.dependent_task_name) {
                dependencyInfo = `<p><strong>Depends on:</strong> ${escapeHTML(task.dependent_task_name)}</p>`;
            } else if (task.dependent_on_task_id) {
                dependencyInfo = `<p><strong>Depends on:</strong> Task ID ${task.dependent_on_task_id} (Name N/A)</p>`;
            }
            taskDiv.innerHTML = `
                <h5>${escapeHTML(task.task_name)}</h5>
                <p><strong>Description:</strong> ${escapeHTML(task.task_description || 'N/A')}</p>
                <p><strong>Start Date:</strong> ${task.start_date || 'N/A'}</p>
                <p><strong>Status:</strong> ${escapeHTML(task.status)} | <strong>Priority:</strong> ${escapeHTML(task.priority)}</p>
                <p><strong>Assignee:</strong> ${escapeHTML(task.assignee_name || 'Unassigned')} | <strong>Due Date:</strong> ${task.due_date || 'N/A'}</p>
                ${dependencyInfo}
                <div class="actions">
                    <button class="edit-task-btn" data-task-id="${task.task_id}">Edit</button>
                    <button class="delete-task-btn" data-task-id="${task.task_id}">Delete</button>
                </div>`;
            tasksListDiv.appendChild(taskDiv);
        });
        tasksListDiv.querySelectorAll('.edit-task-btn').forEach(btn => btn.addEventListener('click', () => editTask(btn.dataset.taskId)));
        tasksListDiv.querySelectorAll('.delete-task-btn').forEach(btn => btn.addEventListener('click', () => deleteTask(btn.dataset.taskId)));
    }

    function editTask(taskIdToEdit) {
        const task = projectTasks.find(t => t.task_id == taskIdToEdit);
        if (task) {
            taskForm.reset();
            taskForm.querySelector('#taskId').value = task.task_id;
            taskForm.querySelector('#taskProjectId').value = task.project_id;
            taskForm.querySelector('#taskName').value = task.task_name;
            taskForm.querySelector('#taskDescription').value = task.task_description || '';
            
            if (taskStartDateInput) { 
                taskStartDateInput.value = task.start_date || '';
            }
            
            taskForm.querySelector('#taskStatus').value = task.status;
            taskForm.querySelector('#taskPriority').value = task.priority;
            taskAssigneeSelect.value = task.assignee_id || '';
            taskForm.querySelector('#taskDueDate').value = task.due_date || '';
            populateTaskDependencyDropdown(task.task_id);
            taskDependencySelect.value = task.dependent_on_task_id || '';
            taskFormContainer.style.display = 'block';
            taskFormContainer.scrollIntoView({ behavior: 'smooth' });
        }
    }

    async function deleteTask(taskIdToDelete) {
        if (!confirm('Are you sure you want to delete this task?')) return;
        try {
            const formData = new FormData(); formData.append('action', 'deleteTask'); formData.append('task_id', taskIdToDelete);
            const data = await fetchData(TASKS_API, { method: 'POST', body: formData });
            if (data.success) { showNotification(data.message, 'success'); loadTasksForProject(currentProjectId); } 
            else { showNotification(data.message || 'Failed to delete task.', 'error'); }
        } catch (error) { /* Handled by fetchData */ }
    }

    // --- Task View Switching ---
    showTaskListViewBtn.addEventListener('click', () => switchTaskView('list'));
    showKanbanViewBtn.addEventListener('click', () => switchTaskView('kanban'));
    showGanttViewBtn.addEventListener('click', () => switchTaskView('gantt'));

    function switchTaskView(viewType) {
        taskListView.style.display = 'none'; kanbanView.style.display = 'none'; ganttView.style.display = 'none';
        showTaskListViewBtn.classList.remove('active-view'); showKanbanViewBtn.classList.remove('active-view'); showGanttViewBtn.classList.remove('active-view');
        taskFormContainer.style.display = 'none';

        if (viewType === 'list') { 
            taskListView.style.display = 'block'; showTaskListViewBtn.classList.add('active-view'); 
            renderTasksList(projectTasks); 
        } else if (viewType === 'kanban') { 
            kanbanView.style.display = 'block'; showKanbanViewBtn.classList.add('active-view'); 
            renderKanbanBoard(projectTasks); 
        } else if (viewType === 'gantt') { 
            ganttView.style.display = 'block'; showGanttViewBtn.classList.add('active-view'); 
            renderGanttChart(projectTasks, currentProjectId);
        }
    }
    
    // --- Kanban Board Functions ---
    function clearKanbanColumns() { 
        const kanbanColumnTasks = kanbanView.querySelectorAll('.kanban-column .kanban-tasks');
        if(kanbanColumnTasks) kanbanColumnTasks.forEach(col => col.innerHTML = ''); 
    }

    function renderKanbanBoard(tasks) {
        clearKanbanColumns();
        if (!tasks) return;

        tasks.forEach(task => {
            const taskElement = createKanbanTaskElement(task);
            const columnId = `kanban-${task.status.toLowerCase().replace(/\s+/g, '-')}`;
            const column = kanbanView.querySelector(`#${columnId}`);

            if (column) {
                const tasksContainer = column.querySelector('.kanban-tasks');
                if (tasksContainer) {
                    tasksContainer.appendChild(taskElement);
                } else {
                    console.warn(`Task container (.kanban-tasks) not found within column ID: ${columnId}. Task "${task.task_name}" not placed.`);
                    const todoColumnTasks = kanbanView.querySelector('#kanban-todo .kanban-tasks');
                    if (todoColumnTasks) todoColumnTasks.appendChild(taskElement); else console.error("CRITICAL: 'To Do' Kanban column's task container also not found!");
                }
            } else { 
                console.warn(`Kanban column with ID "${columnId}" NOT FOUND for status: "${task.status}". Placing task "${task.task_name}" in 'To Do'.`);
                const todoColumnTasks = kanbanView.querySelector('#kanban-todo .kanban-tasks');
                if (todoColumnTasks) todoColumnTasks.appendChild(taskElement); else console.error("CRITICAL: 'To Do' Kanban column's task container not found for fallback!");
            }
        });
        addKanbanDragDropListeners();
    }

    function createKanbanTaskElement(task) {
        const div = document.createElement('div');
        div.className = `kanban-task priority-${task.priority}`;
        div.draggable = true; div.dataset.taskId = task.task_id;
        let dependencyText = '';
        if (task.dependent_on_task_id && task.dependent_task_name) {
            dependencyText = `<p class="dependency-info"><small><em>Depends on: ${escapeHTML(task.dependent_task_name)}</em></small></p>`;
        }
        let startDateText = task.start_date ? `<p class="task-meta-kanban"><small>Start: ${task.start_date}</small></p>` : '';

        div.innerHTML = `
            <strong>${escapeHTML(task.task_name)}</strong>
            <p class="task-desc-kanban"><small>${escapeHTML(task.task_description || 'No description')}</small></p>
            ${dependencyText}
            ${startDateText} 
            <p class="task-meta-kanban"><small>Due: ${task.due_date || 'N/A'} | Assignee: ${escapeHTML(task.assignee_name || 'Unassigned')}</small></p>`;
        div.addEventListener('click', (e) => { 
            if (e.target.closest('button')) return;
            editTask(task.task_id); 
        });
        return div;
    }
    
    let draggedTask = null;
    function addKanbanDragDropListeners() {
        const draggableTasks = kanbanView.querySelectorAll('.kanban-task');
        draggableTasks.forEach(task => {
            task.addEventListener('dragstart', (e) => {
                draggedTask = e.target; e.dataTransfer.setData('text/plain', e.target.dataset.taskId);
                setTimeout(() => { if(draggedTask) draggedTask.classList.add('dragging'); }, 0);
            });
            task.addEventListener('dragend', (e) => { 
                if(draggedTask) draggedTask.classList.remove('dragging'); 
                draggedTask = null; 
            });
        });
        const dropZones = kanbanView.querySelectorAll('.kanban-column .kanban-tasks');
        dropZones.forEach(columnContainer => {
            columnContainer.addEventListener('dragover', (e) => { e.preventDefault(); columnContainer.parentElement.classList.add('drag-over'); });
            columnContainer.addEventListener('dragleave', () => columnContainer.parentElement.classList.remove('drag-over'));
            columnContainer.addEventListener('drop', async (e) => {
                e.preventDefault(); columnContainer.parentElement.classList.remove('drag-over');
                if (draggedTask && e.currentTarget.contains(draggedTask) === false) {
                    const targetColumnDiv = e.currentTarget.closest('.kanban-column');
                    if (!targetColumnDiv) { 
                        console.error("Could not find target Kanban column for drop.");
                        draggedTask = null;
                        return;
                    }
                    const newStatus = targetColumnDiv.dataset.status;
                    const taskId = draggedTask.dataset.taskId;
                    e.currentTarget.appendChild(draggedTask); 
                    try {
                        const formData = new FormData(); formData.append('action', 'updateTaskStatus'); formData.append('task_id', taskId); formData.append('status', newStatus);
                        const data = await fetchData(TASKS_API, { method: 'POST', body: formData });
                        if (data.success) {
                            showNotification(data.message || 'Task status updated!', 'success');
                            const taskToUpdate = projectTasks.find(t => t.task_id == taskId);
                            if (taskToUpdate) taskToUpdate.status = newStatus;
                        } else { 
                            showNotification(data.message || 'Failed to update task status.', 'error');
                            loadTasksForProject(currentProjectId); // Re-render to revert visual change
                        }
                    } catch (error) { loadTasksForProject(currentProjectId); } // Re-render on error
                }
                draggedTask = null;
            });
        });
    }

    // --- Gantt Chart (Frappe Gantt Integration) ---
    function renderGanttChart(tasks, projectIdForGantt) {
        ganttChartContainer.innerHTML = ''; 
        const useFrappeGantt = typeof Gantt !== 'undefined';

        if (useFrappeGantt) {
            if (!tasks || tasks.length === 0) {
                ganttChartContainer.innerHTML = '<p>No tasks with dates to display in Gantt chart.</p>';
                return;
            }
            const ganttTasks = tasks.map(task => {
                let startDateForGantt = task.start_date || (task.created_at ? task.created_at.split(' ')[0] : null);
                let endDateForGantt = task.due_date; 
                
                if (!endDateForGantt && startDateForGantt) { 
                    const tempDate = new Date(startDateForGantt);
                    tempDate.setDate(tempDate.getDate() + 1);
                    endDateForGantt = tempDate.toISOString().split('T')[0];
                } else if (!startDateForGantt && endDateForGantt) { 
                    startDateForGantt = endDateForGantt;
                }

                if (!startDateForGantt || !endDateForGantt || new Date(startDateForGantt) > new Date(endDateForGantt)) { 
                    console.warn(`Task "${task.task_name}" (ID: ${task.task_id}) has invalid/missing dates for Gantt. Start: ${startDateForGantt}, End: ${endDateForGantt}. Skipping.`);
                    return null; 
                }
                
                const statusClass = `status-${task.status.toLowerCase().replace(/\s+/g, '-')}`;
                const taskColorClass = `gantt-task-color-${parseInt(task.task_id) % 5}`;

                return {
                    id: String(task.task_id), 
                    name: escapeHTML(task.task_name),
                    start: startDateForGantt, 
                    end: endDateForGantt,      
                    progress: task.status === 'Done' ? 100 : (task.status === 'In Progress' ? 30 : 0), 
                    dependencies: task.dependent_on_task_id ? String(task.dependent_on_task_id) : null,
                    custom_class: `${statusClass} ${taskColorClass}`
                };
            }).filter(Boolean);

            if (ganttTasks.length === 0) {
                ganttChartContainer.innerHTML = '<p>No tasks with valid start/due dates for Gantt chart.</p>';
                return;
            }
            try {
                    new Gantt("#ganttChartContainer", ganttTasks, {
                    header_height: 50,
                    column_width: 30, 
                    step: 24,
                    view_modes: ['Quarter Day', 'Half Day', 'Day', 'Week', 'Month'], 
                    bar_height: 20,
                    bar_corner_radius: 3,
                    arrow_curve: 5, 
                    padding: 18,
                    view_mode: 'Week', 
                    date_format: 'YYYY-MM-DD',
                    language: 'en', 
                    on_click: function (task) {
                        editTask(task.id);
                    },
                    on_date_change: function (task, start, end) {
                        console.log(`Visual attempt to change ${task.name} dates to: ${start} - ${end}. Backend update not implemented for Gantt drag/resize.`);
                    },
                    on_progress_change: function (task, progress) {
                        console.log(`Visual attempt to change ${task.name} progress to: ${progress}%. Backend update not implemented for Gantt progress drag.`);
                    },
                    custom_popup_html: function(taskGantt) {
                        const originalTask = projectTasks.find(t => String(t.task_id) === taskGantt.id);
                        const startDate = new Date(taskGantt.start).toLocaleDateString();
                        const endDate = new Date(taskGantt.end).toLocaleDateString();
                        let dependencyInfo = '';
                        if (taskGantt.dependencies) {
                            const dependentTaskOriginal = projectTasks.find(t => String(t.task_id) === taskGantt.dependencies);
                            if (dependentTaskOriginal) {
                                dependencyInfo = `<p><strong>Depends on:</strong> ${escapeHTML(dependentTaskOriginal.task_name)}</p>`;
                            } else {
                                dependencyInfo = `<p><strong>Depends on ID:</strong> ${taskGantt.dependencies}</p>`;
                            }
                        }
                        let assigneeName = 'Unassigned';
                        if (originalTask && originalTask.assignee_name) {
                            assigneeName = escapeHTML(originalTask.assignee_name);
                        } else if (originalTask && originalTask.assignee_id) {
                            const assignee = allUsers.find(u => u.user_id == originalTask.assignee_id);
                            if (assignee) assigneeName = escapeHTML(assignee.user_name);
                        }
                        
                        return `
                            <div class="gantt-popup">
                                <h5>${taskGantt.name}</h5>
                                <p><strong>Starts:</strong> ${startDate}</p>
                                <p><strong>Ends:</strong> ${endDate}</p>
                                <p><strong>Status:</strong> ${originalTask ? escapeHTML(originalTask.status) : 'N/A'}</p>
                                <p><strong>Assignee:</strong> ${assigneeName}</p>
                                ${dependencyInfo}
                                <p><small><em>Click bar to edit task details.</em></small></p>
                            </div>
                        `;
                    }
                });
            } catch(e) {
                console.error("Frappe Gantt Error:", e);
                ganttChartContainer.innerHTML = '<p>Error loading Gantt Chart. Ensure Frappe Gantt library is included and task dates are valid.</p>';
            }
        } else {
            ganttChartContainer.innerHTML = `
                <p style="text-align:center; padding:20px;">
                    Advanced Gantt Chart library (like Frappe Gantt) is not loaded.
                </p>`;
        }
    }

    // --- Initial Application Load ---
    async function initializeApp() {
        await loadUsers(); 
        showDashboard();    
    }

    initializeApp();
});