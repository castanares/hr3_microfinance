<?php
include '../layout/Layout.php';

// employees will be loaded via AJAX from ../api/employees.php

$children = "
<div class='p-8 bg-gray-50 min-h-screen'>
    <div class='flex items-center justify-between mb-8'>
        <h1 class='text-3xl font-bold text-gray-800'>Employee List</h1>
        <button id='openAddEmployeeModal' class='bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition'>Add Employee</button>
    </div>
    <!-- Add Employee Modal -->
    <div id='addEmployeeModal' class='fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden'>
        <div class='bg-white rounded-xl shadow-lg p-8 max-w-md w-full relative'>
            <button id='closeAddEmployeeModal' class='absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-2xl'>&times;</button>
            <h2 class='text-2xl font-bold mb-6'>Add Employee</h2>
            <form id='addEmployeeForm'>
                <div class='mb-4'>
                    <label class='block text-gray-700 font-semibold mb-2' for='emp_name'>Name</label>
                    <input type='text' id='emp_name' name='emp_name' class='w-full border rounded-lg p-2' required>
                </div>
                <div class='mb-4'>
                    <label class='block text-gray-700 font-semibold mb-2' for='emp_position'>Position</label>
                    <input type='text' id='emp_position' name='emp_position' class='w-full border rounded-lg p-2' required>
                </div>
                <div class='mb-4'>
                    <label class='block text-gray-700 font-semibold mb-2' for='emp_email'>Email</label>
                    <input type='email' id='emp_email' name='emp_email' class='w-full border rounded-lg p-2' required>
                </div>
                <div class='mb-4'>
                    <label class='block text-gray-700 font-semibold mb-2' for='emp_rfid'>RFID Value </label>
                    <input type='text' id='emp_rfid' name='emp_rfid' class='w-full border rounded-lg p-2' required placeholder='scan new employee RFID'>
                </div>
                <button type='submit' class='bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition w-full'>Add Employee</button>
            </form>
        </div>
    </div>
    <!-- Edit Employee Modal -->
    <div id='editEmployeeModal' class='fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden'>
        <div class='bg-white rounded-xl shadow-lg p-8 max-w-md w-full relative'>
            <button id='closeEditEmployeeModal' class='absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-2xl'>&times;</button>
            <h2 class='text-2xl font-bold mb-6'>Edit Employee</h2>
            <form id='editEmployeeForm'>
                <input type='hidden' id='edit_emp_id' name='edit_emp_id'>
                <div class='mb-4'>
                    <label class='block text-gray-700 font-semibold mb-2' for='edit_emp_name'>Name</label>
                    <input type='text' id='edit_emp_name' name='edit_emp_name' class='w-full border rounded-lg p-2' required>
                </div>
                <div class='mb-4'>
                    <label class='block text-gray-700 font-semibold mb-2' for='edit_emp_position'>Position</label>
                    <input type='text' id='edit_emp_position' name='edit_emp_position' class='w-full border rounded-lg p-2' required>
                </div>
                <div class='mb-4'>
                    <label class='block text-gray-700 font-semibold mb-2' for='edit_emp_email'>Email</label>
                    <input type='email' id='edit_emp_email' name='edit_emp_email' class='w-full border rounded-lg p-2' required>
                </div>
                <div class='mb-4'>
                    <label class='block text-gray-700 font-semibold mb-2' for='edit_emp_rfid'>RFID Value</label>
                    <input type='text' id='edit_emp_rfid' name='edit_emp_rfid' class='w-full border rounded-lg p-2' required placeholder='scan new employee RFID'>
                </div>
                <button type='submit' class='bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition w-full'>Save Changes</button>
            </form>
        </div>
    </div>
    <div class='bg-white rounded-xl shadow p-8 overflow-x-auto'>
        <table class='w-full table-auto border-collapse border border-gray-200 text-left'>
            <thead class='bg-gray-100'>
                <tr>
                    <th class='px-4 py-2 border border-gray-200'>#</th>
                    <th class='px-4 py-2 border border-gray-200'>Name</th>
                    <th class='px-4 py-2 border border-gray-200'>Position</th>
                    <th class='px-4 py-2 border border-gray-200'>Email</th>
                    <th class='px-4 py-2 border border-gray-200'>Actions</th>
                </tr>
            </thead>
            <tbody id='employeesTableBody'>
                <!-- rows will be injected by JS -->
            </tbody>
        </table>
    </div>
</div>
<script>
// Add Employee Modal logic
const addEmployeeModal = document.getElementById('addEmployeeModal');
const openAddEmployeeModal = document.getElementById('openAddEmployeeModal');
const closeAddEmployeeModal = document.getElementById('closeAddEmployeeModal');
openAddEmployeeModal.addEventListener('click', function(e) {
    e.preventDefault();
    addEmployeeModal.classList.remove('hidden');
});
closeAddEmployeeModal.addEventListener('click', function() {
    addEmployeeModal.classList.add('hidden');
    document.getElementById('addEmployeeForm').reset();
});
// Edit Employee Modal logic (only close/reset here; opening is handled by fetch-based handler)
const editEmployeeModal = document.getElementById('editEmployeeModal');
const closeEditEmployeeModal = document.getElementById('closeEditEmployeeModal');
const editEmployeeForm = document.getElementById('editEmployeeForm');
closeEditEmployeeModal.addEventListener('click', function() {
    editEmployeeModal.classList.add('hidden');
    editEmployeeForm.reset();
});
</script>

";

Layout($children);
?>
<script>
// Frontend wiring to REST API for employees
const apiBase = '../api/employees.php';
const employeesTableBody = document.getElementById('employeesTableBody');

// Toast container
const toastContainer = document.createElement('div');
toastContainer.id = 'toastContainer';
toastContainer.style.position = 'fixed';
toastContainer.style.right = '20px';
toastContainer.style.top = '20px';
toastContainer.style.zIndex = '9999';
document.body.appendChild(toastContainer);

function showToast(message, type = 'info', timeout = 4000) {
    const bg = type === 'success' ? '#16a34a' : (type === 'error' ? '#dc2626' : '#0ea5e9');
    const el = document.createElement('div');
    el.style.background = bg;
    el.style.color = 'white';
    el.style.padding = '10px 14px';
    el.style.marginTop = '8px';
    el.style.borderRadius = '8px';
    el.style.boxShadow = '0 4px 12px rgba(0,0,0,0.08)';
    el.style.maxWidth = '320px';
    el.style.fontFamily = 'Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial';
    el.innerText = message;
    toastContainer.appendChild(el);
    setTimeout(() => { el.style.transition = 'opacity 300ms ease'; el.style.opacity = '0'; setTimeout(()=> el.remove(), 300); }, timeout);
}

async function loadEmployees(){
    const res = await fetch(apiBase);
    const data = await res.json();
    employeesTableBody.innerHTML = '';
    data.forEach((emp, idx) => {
        const tr = document.createElement('tr'); tr.className = 'hover:bg-gray-50';
        // show incrementing row number in first column (idx starts at 0)
        tr.innerHTML = `
            <td class='px-4 py-2 border border-gray-200'>${idx + 1}</td>
            <td class='px-4 py-2 border border-gray-200'>${escapeHtml(emp.name)}</td>
            <td class='px-4 py-2 border border-gray-200'>${escapeHtml(emp.position||'')}</td>
            <td class='px-4 py-2 border border-gray-200'>${escapeHtml(emp.email||'')}</td>
            <td class='px-4 py-2 border border-gray-200'>
                <a href='#' class='text-blue-600 hover:underline mr-4 edit-employee-btn' data-id='${emp.id}'>Edit</a>
                <a href='#' class='text-red-600 hover:underline delete-employee-btn' data-id='${emp.id}'>Delete</a>
            </td>`;
        employeesTableBody.appendChild(tr);
    });
    // attach handlers
    document.querySelectorAll('.edit-employee-btn').forEach(btn=> btn.addEventListener('click', onClickEdit));
    document.querySelectorAll('.delete-employee-btn').forEach(btn=> btn.addEventListener('click', onClickDelete));
}

/**
 * Utilities
 */
function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, function (m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
    });
}


/**
 * Event handler -- Edit button
 * Fetch single employee then populate and show the edit modal
 */
async function onClickEdit(e) {
    e.preventDefault();
    const id = this.getAttribute('data-id');
    try {
        const res = await fetch(apiBase + '?id=' + encodeURIComponent(id));
        if (!res.ok) throw new Error('Failed to fetch employee');
        const emp = await res.json();
        document.getElementById('edit_emp_id').value = emp.id || '';
        document.getElementById('edit_emp_name').value = emp.name || '';
        document.getElementById('edit_emp_position').value = emp.position || '';
        document.getElementById('edit_emp_email').value = emp.email || '';
        document.getElementById('edit_emp_rfid').value = emp.rfid || '';
        editEmployeeModal.classList.remove('hidden');
    } catch (err) {
        console.error(err);
        showToast('Could not load employee details', 'error');
    }
}


/**
 * Event handler -- Delete button
 * Asks for confirmation then calls DELETE and refreshes list on success
 */
async function onClickDelete(e) {
    e.preventDefault();
    if (!confirm('Delete this employee?')) return;
    const id = this.getAttribute('data-id');
    try {
        const res = await fetch(apiBase + '?id=' + encodeURIComponent(id), { method: 'DELETE' });
        if (!res.ok) throw new Error('Delete request failed');
        const j = await res.json();
        if (j.ok) {
            showToast('Employee deleted', 'success');
            await loadEmployees();
        } else {
            showToast('Delete failed', 'error');
        }
    } catch (err) {
        console.error(err);
        showToast('Delete failed: ' + (err.message || ''), 'error');
    }
}


/**
 * Add employee form submit handler
 */
document.getElementById('addEmployeeForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    const body = {
        name: fd.get('emp_name'),
        position: fd.get('emp_position'),
        email: fd.get('emp_email'),
        rfid: fd.get('emp_rfid')
    };

    try {
        const res = await fetch(apiBase, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const j = await res.json();
        if (j.id) {
            this.reset();
            addEmployeeModal.classList.add('hidden');
            showToast('Employee added', 'success');
            await loadEmployees();
        } else {
            showToast('Save failed', 'error');
        }
    } catch (err) {
        console.error(err);
        showToast('Save failed: ' + (err.message || ''), 'error');
    }
});


/**
 * Edit employee form submit handler
 */
document.getElementById('editEmployeeForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const id = document.getElementById('edit_emp_id').value;
    const body = {
        name: document.getElementById('edit_emp_name').value,
        position: document.getElementById('edit_emp_position').value,
        email: document.getElementById('edit_emp_email').value,
        rfid: document.getElementById('edit_emp_rfid').value
    };

    try {
        const res = await fetch(apiBase + '?id=' + encodeURIComponent(id), {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const j = await res.json();
        if (j.ok) {
            editEmployeeModal.classList.add('hidden');
            showToast('Employee updated', 'success');
            await loadEmployees();
        } else {
            showToast('Update failed', 'error');
        }
    } catch (err) {
        console.error(err);
        showToast('Update failed: ' + (err.message || ''), 'error');
    }
});


// initial load
loadEmployees().catch(err => { console.error(err); showToast('Failed to load employees', 'error'); });
</script>
