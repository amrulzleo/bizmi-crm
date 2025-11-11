/**
 * BizMi CRM Installation JavaScript
 * 
 * Handles the installation wizard functionality
 * Created by: Amrullah Khan
 * Email: amrulzlionheart@gmail.com
 * Date: November 11, 2025
 * Version: 1.0.0
 */

let currentStep = 1;
const totalSteps = 5;
let dbConnectionVerified = false;

// Initialize installation wizard
document.addEventListener('DOMContentLoaded', function() {
    checkRequirements();
});

// Navigation functions
function nextStep() {
    if (currentStep < totalSteps && validateCurrentStep()) {
        currentStep++;
        updateStepIndicator();
        showStep(currentStep);
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        updateStepIndicator();
        showStep(currentStep);
    }
}

function showStep(step) {
    // Hide all sections
    document.querySelectorAll('.form-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Show current section
    const currentSection = document.querySelector(`[data-section="${step}"]`);
    if (currentSection) {
        currentSection.classList.add('active');
    }
}

function updateStepIndicator() {
    document.querySelectorAll('.step').forEach((step, index) => {
        const stepNumber = index + 1;
        step.classList.remove('active', 'completed');
        
        if (stepNumber < currentStep) {
            step.classList.add('completed');
        } else if (stepNumber === currentStep) {
            step.classList.add('active');
        }
    });
}

function validateCurrentStep() {
    switch (currentStep) {
        case 2:
            return validateRequirements();
        case 3:
            return dbConnectionVerified;
        case 4:
            return validateAdminForm();
        default:
            return true;
    }
}

// System requirements checking
function checkRequirements() {
    const requirements = [
        { name: 'PHP Version', check: 'php_version', required: '8.0+' },
        { name: 'MySQL Extension', check: 'mysql_extension', required: 'Required' },
        { name: 'JSON Extension', check: 'json_extension', required: 'Required' },
        { name: 'cURL Extension', check: 'curl_extension', required: 'Required' },
        { name: 'GD Extension', check: 'gd_extension', required: 'Required' },
        { name: 'ZIP Extension', check: 'zip_extension', required: 'Required' },
        { name: 'Memory Limit', check: 'memory_limit', required: '256MB+' }
    ];
    
    const permissions = [
        { path: '../config/', name: 'Config Directory', required: 'Write' },
        { path: '../uploads/', name: 'Uploads Directory', required: 'Write' },
        { path: '../logs/', name: 'Logs Directory', required: 'Write' },
        { path: '../cache/', name: 'Cache Directory', required: 'Write' }
    ];
    
    // Check requirements
    const requirementsList = document.getElementById('requirements-list');
    requirementsList.innerHTML = '';
    
    requirements.forEach(req => {
        const div = document.createElement('div');
        div.className = 'requirement';
        div.innerHTML = `
            <div>
                <strong>${req.name}</strong>
                <div class="text-muted small">${req.required}</div>
            </div>
            <div class="requirement-status" id="${req.check}">
                <i class="fas fa-spinner fa-spin"></i> Checking...
            </div>
        `;
        requirementsList.appendChild(div);
    });
    
    // Check permissions
    const permissionsList = document.getElementById('permissions-list');
    permissionsList.innerHTML = '';
    
    permissions.forEach(perm => {
        const div = document.createElement('div');
        div.className = 'requirement';
        div.innerHTML = `
            <div>
                <strong>${perm.name}</strong>
                <div class="text-muted small">${perm.path}</div>
            </div>
            <div class="requirement-status" id="perm_${perm.name.toLowerCase().replace(' ', '_')}">
                <i class="fas fa-spinner fa-spin"></i> Checking...
            </div>
        `;
        permissionsList.appendChild(div);
    });
    
    // Make AJAX request to check requirements
    fetch('check_requirements.php')
        .then(response => response.json())
        .then(data => {
            updateRequirementsDisplay(data);
        })
        .catch(error => {
            console.error('Error checking requirements:', error);
            showError('Failed to check system requirements');
        });
}

function updateRequirementsDisplay(data) {
    let allRequirementsMet = true;
    
    // Update requirements
    Object.keys(data.requirements).forEach(key => {
        const element = document.getElementById(key);
        const result = data.requirements[key];
        
        if (element) {
            if (result.status === 'ok') {
                element.innerHTML = `<i class="fas fa-check"></i> ${result.value}`;
                element.className = 'requirement-status ok';
            } else if (result.status === 'warning') {
                element.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${result.value}`;
                element.className = 'requirement-status warning';
            } else {
                element.innerHTML = `<i class="fas fa-times"></i> ${result.value}`;
                element.className = 'requirement-status error';
                allRequirementsMet = false;
            }
        }
    });
    
    // Update permissions
    Object.keys(data.permissions).forEach(key => {
        const element = document.getElementById(`perm_${key}`);
        const result = data.permissions[key];
        
        if (element) {
            if (result.status === 'ok') {
                element.innerHTML = `<i class="fas fa-check"></i> Writable`;
                element.className = 'requirement-status ok';
            } else {
                element.innerHTML = `<i class="fas fa-times"></i> Not Writable`;
                element.className = 'requirement-status error';
                allRequirementsMet = false;
            }
        }
    });
    
    // Enable/disable next button
    const nextButton = document.getElementById('req-next');
    if (nextButton) {
        nextButton.disabled = !allRequirementsMet;
        if (allRequirementsMet) {
            nextButton.innerHTML = 'Continue <i class="fas fa-arrow-right"></i>';
        } else {
            nextButton.innerHTML = 'Requirements Not Met';
        }
    }
}

function validateRequirements() {
    const errorElements = document.querySelectorAll('.requirement-status.error');
    return errorElements.length === 0;
}

// Database connection testing
function testDbConnection() {
    const formData = new FormData();
    formData.append('action', 'test_db');
    formData.append('db_host', document.getElementById('db_host').value);
    formData.append('db_port', document.getElementById('db_port').value);
    formData.append('db_name', document.getElementById('db_name').value);
    formData.append('db_username', document.getElementById('db_username').value);
    formData.append('db_password', document.getElementById('db_password').value);
    
    const resultDiv = document.getElementById('db-connection-result');
    resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Testing connection...</div>';
    
    fetch('install.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check"></i> Database connection successful!</div>';
            dbConnectionVerified = true;
            document.getElementById('db-next').disabled = false;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times"></i> Connection failed: ${data.message}</div>`;
            dbConnectionVerified = false;
            document.getElementById('db-next').disabled = true;
        }
    })
    .catch(error => {
        console.error('Error testing database connection:', error);
        resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times"></i> Failed to test database connection</div>';
        dbConnectionVerified = false;
        document.getElementById('db-next').disabled = true;
    });
}

// Admin form validation
function validateAdminForm() {
    const firstName = document.getElementById('admin_first_name').value.trim();
    const lastName = document.getElementById('admin_last_name').value.trim();
    const email = document.getElementById('admin_email').value.trim();
    const username = document.getElementById('admin_username').value.trim();
    const password = document.getElementById('admin_password').value;
    const passwordConfirm = document.getElementById('admin_password_confirm').value;
    
    // Basic validation
    if (!firstName || !lastName || !email || !username || !password) {
        showError('Please fill in all required fields');
        return false;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showError('Please enter a valid email address');
        return false;
    }
    
    // Password validation
    if (password.length < 8) {
        showError('Password must be at least 8 characters long');
        return false;
    }
    
    if (password !== passwordConfirm) {
        showError('Passwords do not match');
        return false;
    }
    
    return true;
}

// Installation process
function startInstallation() {
    if (!validateAdminForm()) {
        return;
    }
    
    document.getElementById('start-install').disabled = true;
    document.getElementById('install-navigation').style.display = 'none';
    
    const steps = [
        { name: 'Creating configuration files', action: 'create_config' },
        { name: 'Creating database tables', action: 'create_database' },
        { name: 'Inserting default data', action: 'insert_data' },
        { name: 'Creating admin account', action: 'create_admin' },
        { name: 'Finalizing installation', action: 'finalize' }
    ];
    
    const stepsContainer = document.getElementById('installation-steps');
    stepsContainer.innerHTML = '';
    
    steps.forEach((step, index) => {
        const div = document.createElement('div');
        div.className = 'installation-step mb-2';
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <span>${step.name}</span>
                <span class="step-status" id="step-${index}">
                    <i class="fas fa-clock text-muted"></i> Waiting...
                </span>
            </div>
        `;
        stepsContainer.appendChild(div);
    });
    
    executeInstallationSteps(steps, 0);
}

function executeInstallationSteps(steps, currentStepIndex) {
    if (currentStepIndex >= steps.length) {
        completeInstallation();
        return;
    }
    
    const step = steps[currentStepIndex];
    const statusElement = document.getElementById(`step-${currentStepIndex}`);
    
    // Update status to running
    statusElement.innerHTML = '<i class="fas fa-spinner fa-spin text-primary"></i> Running...';
    
    // Update progress bar
    const progress = ((currentStepIndex + 1) / steps.length) * 100;
    const progressBar = document.getElementById('install-progress-bar');
    progressBar.style.width = `${progress}%`;
    progressBar.textContent = `${Math.round(progress)}%`;
    
    // Collect form data
    const formData = new FormData();
    formData.append('action', step.action);
    
    // Add database configuration
    formData.append('db_host', document.getElementById('db_host').value);
    formData.append('db_port', document.getElementById('db_port').value);
    formData.append('db_name', document.getElementById('db_name').value);
    formData.append('db_username', document.getElementById('db_username').value);
    formData.append('db_password', document.getElementById('db_password').value);
    formData.append('db_prefix', document.getElementById('db_prefix').value);
    
    // Add admin configuration
    formData.append('admin_first_name', document.getElementById('admin_first_name').value);
    formData.append('admin_last_name', document.getElementById('admin_last_name').value);
    formData.append('admin_email', document.getElementById('admin_email').value);
    formData.append('admin_username', document.getElementById('admin_username').value);
    formData.append('admin_password', document.getElementById('admin_password').value);
    
    fetch('install.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusElement.innerHTML = '<i class="fas fa-check text-success"></i> Complete';
            // Continue to next step
            setTimeout(() => {
                executeInstallationSteps(steps, currentStepIndex + 1);
            }, 500);
        } else {
            statusElement.innerHTML = '<i class="fas fa-times text-danger"></i> Failed';
            showError(`Installation failed at step "${step.name}": ${data.message}`);
        }
    })
    .catch(error => {
        console.error('Installation error:', error);
        statusElement.innerHTML = '<i class="fas fa-times text-danger"></i> Error';
        showError(`Installation failed at step "${step.name}"`);
    });
}

function completeInstallation() {
    document.getElementById('installation-progress').style.display = 'none';
    document.getElementById('installation-complete').style.display = 'block';
}

// Utility functions
function showError(message) {
    alert(message); // Replace with a better notification system
}

function showSuccess(message) {
    alert(message); // Replace with a better notification system
}

// Form field validations
document.addEventListener('input', function(e) {
    if (e.target.id === 'admin_password_confirm') {
        const password = document.getElementById('admin_password').value;
        const confirm = e.target.value;
        
        if (confirm && password !== confirm) {
            e.target.setCustomValidity('Passwords do not match');
        } else {
            e.target.setCustomValidity('');
        }
    }
});

// Auto-fill username from email
document.getElementById('admin_email').addEventListener('input', function(e) {
    const email = e.target.value;
    const usernameField = document.getElementById('admin_username');
    
    if (!usernameField.value && email) {
        // Extract username part from email
        const username = email.split('@')[0].replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
        if (username.length >= 3) {
            usernameField.value = username;
        }
    }
});

// Test database connection when Enter is pressed
document.querySelectorAll('#db_host, #db_name, #db_username, #db_password').forEach(field => {
    field.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            testDbConnection();
        }
    });
});