<?php
include '../layout/Layout.php';

date_default_timezone_set('Asia/Manila');

// shifts will be loaded from API

$children = '
<div class="p-6 bg-gray-50 min-h-screen">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-2">
            <i class="bx bx-calendar text-green-600 text-3xl"></i>
            <h1 class="text-3xl font-bold text-gray-800">View Shifts</h1>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-500">Today: ' . date("F d, Y") . '</span>
            <button id="openAddShiftModal" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Add Shift</button>
        </div>
    </div>





    <!-- Add/Edit Shift Modal -->
    <div id="addShiftModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-lg p-8 max-w-md w-full relative">
            <button id="closeAddShiftModal" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            <h2 class="text-2xl font-bold mb-6" id="modalTitle">Add Shift</h2>
            <form id="addShiftForm">
                <input type="hidden" id="shift_id" name="shift_id">
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="shift_employee">Employee</label>
                    <select id="shift_employee" name="shift_employee" class="w-full border rounded-lg p-2" required>
                        <option value="">Select Employee</option>
                        <!-- Options will be populated by JS -->
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="shift_department">Department</label>
                    <input type="text" id="shift_department" name="shift_department" class="w-full border rounded-lg p-2" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="shift_name">Shift</label>
                    <select id="shift_name" name="shift_name" class="w-full border rounded-lg p-2" required>
                        <option value="">Select Shift</option>
                        <option value="Morning">Morning</option>
                        <option value="Afternoon">Afternoon</option>
                        <option value="Night">Night</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="shift_start_time">Start Time</label>
                    <input type="time" id="shift_start_time" name="shift_start_time" class="w-full border rounded-lg p-2" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="shift_end_time">End Time</label>
                    <input type="time" id="shift_end_time" name="shift_end_time" class="w-full border rounded-lg p-2" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="shift_date">Date</label>
                    <input type="date" id="shift_date" name="shift_date" class="w-full border rounded-lg p-2" value="' . date("Y-m-d") . '" required>
                </div>
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition w-full" id="submitBtn">Add Shift</button>
            </form>
        </div>
    </div>

    <!-- Filter: date picker placed above table -->
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3 bg-white p-3 rounded-lg shadow-sm">
            <label for="shiftFilterDate" class="text-sm text-gray-600">Date</label>
            <input type="date" id="shiftFilterDate" class="border rounded-md p-2" value="' . date("Y-m-d") . '">
            <button id="clearShiftFilter" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-md hover:bg-gray-200">Clear</button>
        </div>
        <div class="text-sm text-gray-500">Showing shifts for selected date</div>
    </div>

    <!-- Shifts Table -->
    <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
        <table class="min-w-full table-auto border-collapse border border-gray-200">
            <thead class="bg-white sticky top-0 z-20">
                <tr class="bg-gray-50">
                    <th class="px-4 py-2 border">Employee</th>
                    <th class="px-4 py-2 border">Department</th>
                    <th class="px-4 py-2 border">Shift</th>
                    <th class="px-4 py-2 border">Start Time</th>
                    <th class="px-4 py-2 border">End Time</th>
                    <th class="px-4 py-2 border">Date</th>
                    <th class="px-4 py-2 border">Actions</th>
                </tr>
            </thead>
            <tbody id="shiftsTableBody">
                <!-- rows injected by JS -->
            </tbody>
        </table>
    </div>
</div>



';

Layout($children);
?>
<script>
// Toast container (Tailwind based)
const toastContainer = document.createElement('div');
toastContainer.id = 'toastContainer';
toastContainer.className = 'fixed right-4 top-4 z-50 flex flex-col items-end space-y-2';
document.body.appendChild(toastContainer);

function showToast(message, type = 'info', timeout = 4000) {
    const color = type === 'success' ? 'green' : type === 'error' ? 'red' : 'blue';
    const toast = document.createElement('div');
    toast.className = `w-96 bg-${color}-50 border border-${color}-200 px-4 py-3 rounded shadow flex items-start justify-between`;
    toast.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 text-${color}-600">
                <i class="bx bx-bell"></i>
            </div>
            <div class="text-sm text-${color}-700">${message}</div>
        </div>
        <button aria-label="Close" class="ml-4 text-gray-500 hover:text-gray-700">&times;</button>
    `;
    const closeBtn = toast.querySelector('button');
    closeBtn.addEventListener('click', () => toast.remove());
    toastContainer.appendChild(toast);
    if (timeout > 0) setTimeout(() => { toast.remove(); }, timeout);
}

const shiftsApi = '../api/shifts.php';
const employeesApi = '../api/employees.php';
const shiftsTableBody = document.getElementById('shiftsTableBody');
const addShiftModal = document.getElementById('addShiftModal');
const openAddShiftModal = document.getElementById('openAddShiftModal');
const closeAddShiftModal = document.getElementById('closeAddShiftModal');
const addShiftForm = document.getElementById('addShiftForm');
const employeeSelect = document.getElementById('shift_employee');
const modalTitle = document.getElementById('modalTitle');
const submitBtn = document.getElementById('submitBtn');

// Modal open/close
openAddShiftModal.addEventListener('click', function(e) {
    e.preventDefault();
    modalTitle.textContent = 'Add Shift';
    submitBtn.textContent = 'Add Shift';
    document.getElementById('shift_id').value = '';
    addShiftModal.classList.remove('hidden');
    loadEmployeesForSelect();
});
closeAddShiftModal.addEventListener('click', function() {
    addShiftModal.classList.add('hidden');
    addShiftForm.reset();
});

// Load employees for select
async function loadEmployeesForSelect() {
    try {
        const res = await fetch(employeesApi);
        const data = await res.json();
        employeeSelect.innerHTML = '<option value="">Select Employee</option>';
        data.forEach(emp => {
            const option = document.createElement('option');
            option.value = emp.id;
            option.textContent = emp.name;
            employeeSelect.appendChild(option);
        });
    } catch (err) {
        console.error('Failed to load employees:', err);
        showToast('Failed to load employees', 'error');
    }
}

// Add/Edit shift form submit handler
document.getElementById('addShiftForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    const body = {
        employee_id: fd.get('shift_employee'),
        shift_name: fd.get('shift_name'),
        department: fd.get('shift_department'),
        start_time: fd.get('shift_start_time'),
        end_time: fd.get('shift_end_time'),
        date: fd.get('shift_date')
    };
    const id = fd.get('shift_id');
    const method = id ? 'PUT' : 'POST';
    const url = id ? `${shiftsApi}?id=${id}` : shiftsApi;

    try {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const j = await res.json();
        if ((method === 'POST' && j.id) || (method === 'PUT' && j.ok)) {
            this.reset();
            addShiftModal.classList.add('hidden');
            showToast(`Shift ${method === 'POST' ? 'added' : 'updated'}`, 'success');
            await loadShifts();
        } else {
            showToast('Save failed', 'error');
        }
    } catch (err) {
        console.error(err);
        showToast('Save failed: ' + (err.message || ''), 'error');
    }
});

// Edit shift
async function editShift(shift) {
    modalTitle.textContent = 'Edit Shift';
    submitBtn.textContent = 'Update Shift';
    document.getElementById('shift_id').value = shift.id;
    await loadEmployeesForSelect();  // Wait for employees to load
    document.getElementById('shift_employee').value = shift.employee_id;
    document.getElementById('shift_department').value = shift.department;
    document.getElementById('shift_name').value = shift.shift_name;
    document.getElementById('shift_start_time').value = shift.start_time;
    document.getElementById('shift_end_time').value = shift.end_time;
    document.getElementById('shift_date').value = shift.date;
    addShiftModal.classList.remove('hidden');
}

// Delete shift
async function deleteShift(id) {
    if (!confirm('Are you sure you want to delete this shift?')) return;
    try {
        const res = await fetch(`${shiftsApi}?id=${id}`, { method: 'DELETE' });
        const j = await res.json();
        if (j.ok) {
            showToast('Shift deleted', 'success');
            await loadShifts();
        } else {
            showToast('Delete failed', 'error');
        }
    } catch (err) {
        console.error(err);
        showToast('Delete failed: ' + (err.message || ''), 'error');
    }
}

// Helper functions to format time and date
function formatTime(timeStr) {
    if (!timeStr) return '-';
    const [h, m] = timeStr.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${m} ${ampm}`;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const [y, m, d] = dateStr.split('-');
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    return `${monthNames[parseInt(m) - 1]} ${parseInt(d)}, ${y}`;
}

// Load shifts (with client-side date filter)
async function loadShifts(){
    try {
        const res = await fetch(shiftsApi);
        const data = await res.json();
        shiftsTableBody.innerHTML = '';
        const filterEl = document.getElementById('shiftFilterDate');
        const filterVal = filterEl ? filterEl.value : '';
        const rows = filterVal ? data.filter(s => s.date === filterVal) : data;
        if (!rows.length) {
            shiftsTableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4">No shifts for selected date.</td></tr>';
            return;
        }
        rows.forEach((s, idx)=>{
            const tr = document.createElement('tr');
            tr.className = idx % 2 === 0 ? 'bg-white' : 'bg-gray-50';
            tr.innerHTML = `
                <td class="px-4 py-3 border text-sm text-gray-700">${escapeHtml(s.employee_name||'')}</td>
                <td class="px-4 py-3 border text-sm text-gray-600">${escapeHtml(s.department||'')}</td>
                <td class="px-4 py-3 border text-sm font-medium text-gray-800">${escapeHtml(s.shift_name||'')}</td>
                <td class="px-4 py-3 border text-sm">${escapeHtml(formatTime(s.start_time)||'')}</td>
                <td class="px-4 py-3 border text-sm">${escapeHtml(formatTime(s.end_time)||'')}</td>
                <td class="px-4 py-3 border text-sm text-gray-600">${escapeHtml(formatDate(s.date)||'')}</td>
                <td class="px-4 py-3 border text-sm">
                    <div class="flex items-center justify-center gap-2">
                        <button onclick="editShift(${JSON.stringify(s).replace(/"/g, '&quot;')})" class="flex items-center gap-2 bg-blue-50 text-blue-700 px-3 py-1 rounded hover:bg-blue-100">
                            <i class='bx bx-edit-alt'></i> Edit
                        </button>
                        <button onclick="deleteShift(${s.id})" class="flex items-center gap-2 bg-red-50 text-red-700 px-3 py-1 rounded hover:bg-red-100">
                            <i class='bx bx-trash'></i> Delete
                        </button>
                    </div>
                </td>`;
            shiftsTableBody.appendChild(tr);
        });
    } catch (err) {
        console.error('Failed to load shifts:', err);
        showToast('Failed to load shifts', 'error');
    }
}

// Wire filter listeners
const shiftFilterDateEl = document.getElementById('shiftFilterDate');
if (shiftFilterDateEl) {
    shiftFilterDateEl.addEventListener('change', () => loadShifts());
}
const clearShiftFilterBtn = document.getElementById('clearShiftFilter');
if (clearShiftFilterBtn) {
    clearShiftFilterBtn.addEventListener('click', () => {
        if (shiftFilterDateEl) shiftFilterDateEl.value = '';
        loadShifts();
    });
}
function escapeHtml(str){ return String(str||'').replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m]; }); }
loadShifts().catch(e=>console.error(e));
</script>