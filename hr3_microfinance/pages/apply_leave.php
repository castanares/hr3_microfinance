<?php
include '../layout/Layout.php';
date_default_timezone_set('Asia/Manila');

$children = '
<div class="p-6 bg-gray-50 min-h-screen">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2">
            <i class="bx bx-calendar-alt text-red-600 text-3xl"></i>
            <h1 class="text-3xl font-bold text-gray-800">Apply Leave</h1>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-500">Today: ' . date("F d, Y") . '</span>
          
        </div>
    </div>
    <div class="mb-6 text-right">
      
    </div>
    <!-- Button to Open Modal -->
    <div class="mb-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 bg-white rounded-xl shadow-md px-4 py-2">
                    <label for="yearFilter" class="text-sm text-gray-600 font-medium">Year</label>
                    <select id="yearFilter" class="ml-2 font-medium border-none outline-none bg-transparent">
                        <!-- options populated by JS -->
                    </select>
                </div>
            </div>

            <div>
                <button id="openModalBtn" class="bg-red-600 text-white px-6 py-3 rounded-xl hover:bg-red-700 transition shadow-lg text-lg flex items-center gap-2">
                    <i class=" "></i>
                    <span class="font-semibold">Apply Leave</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="leaveModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 relative">
            <button id="closeModalBtn" class="absolute top-3 right-3 text-gray-500 hover:text-gray-800 text-xl">&times;</button>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Apply Leave</h2>
            <form id="applyLeaveForm">
                <input type="hidden" id="leaveId" value="">
                <div class="mb-3">
                    <label class="block text-gray-700 font-semibold mb-1">Employee</label>
                    <select id="employee" class="w-full border rounded-lg p-2">
                        <option value="">Select Employee</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 font-semibold mb-1">Leave Type</label>
                    <select id="leaveType" class="w-full border rounded-lg p-2">
                        <option value="">Select Leave Type</option>
                        <option value="Sick Leave">Sick Leave / Medical Leave</option>
                        <option value="Vacation Leave">Vacation / Annual Leave</option>
                        <option value="Emergency Leave">Emergency Leave</option>
                        <option value="Maternity Leave">Maternity Leave</option>
                        <option value="Paternity Leave">Paternity Leave</option>
                        <option value="Solo Parent Leave">Solo Parent Leave</option>
                        <option value="Bereavement Leave">Bereavement / Compassionate Leave</option>
                        <option value="Study Leave">Study / Training Leave</option>
                        <option value="Service Incentive Leave">Service Incentive Leave</option>
                        <option value="Special Leave for Women">Special Leave for Women</option>
                        <option value="Parental Leave">Parental Leave</option>
                        <option value="Jury Duty">Jury Duty / Civic Duty</option>
                        <option value="Family Care Leave">Family Care Leave</option>
                        <option value="Unpaid Leave">Unpaid Leave</option>
                        <option value="Half-Day Leave">Half-Day / Partial Leave</option>
                        <option value="Others">Others (Specify)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 font-semibold mb-1">Upload Leave Letter (optional)</label>
                    <input type="file" id="leaveLetter" accept="application/pdf,image/*" class="w-full" />
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 font-semibold mb-1">From</label>
                    <input type="date" id="fromDate" class="w-full border rounded-lg p-2" value="' . date("Y-m-d") . '">
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 font-semibold mb-1">To</label>
                    <input type="date" id="toDate" class="w-full border rounded-lg p-2" value="' . date("Y-m-d") . '">
                </div>
                <div class="mt-3">
                    <label class="block text-gray-700 font-semibold mb-1">Status</label>
                    <select id="status" class="w-full border rounded-lg p-2">
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" id="cancelBtn" class="px-4 py-2 rounded-lg border hover:bg-gray-100 transition">Cancel</button>
                    <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">Submit</button>
                </div>
                <p id="msg" class="mt-3 font-semibold text-green-600"></p>
            </form>
        </div>
    </div>

    <!-- Applied Leaves Table -->
    <div class="overflow-x-auto bg-white rounded-xl shadow p-4 mt-6">
        <table class="min-w-full border border-gray-200">
            <thead>
                <tr class="bg-gray-100 text-center">
                    <th class="px-4 py-2 border">Employee</th>
                    <th class="px-4 py-2 border">Leave Type</th>
                    <th class="px-4 py-2 border">From</th>
                    <th class="px-4 py-2 border">To</th>
                    <th class="px-4 py-2 border">Status</th>
                    <th class="px-4 py-2 border">Actions</th>
                </tr>
            </thead>
            <tbody id="leavesTableBody">
                <tr><td colspan="6" class="text-center px-4 py-2">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

';

Layout($children);
?>

<script>
// Modal
const modal = document.getElementById("leaveModal");
document.getElementById("openModalBtn").addEventListener("click", () => {
    modal.classList.remove("hidden");
    document.getElementById("leaveId").value = "";
    // default status for new leave
    const statusEl = document.getElementById('status');
    if (statusEl) statusEl.value = 'Pending';
});
document.getElementById("closeModalBtn").addEventListener("click", () => modal.classList.add("hidden"));
document.getElementById("cancelBtn").addEventListener("click", () => modal.classList.add("hidden"));
window.addEventListener("click", (e) => { if(e.target == modal) modal.classList.add("hidden"); });

// Load leaves
function loadLeaves() {
    fetch("../api/leaves.php")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("leavesTableBody");
        tbody.innerHTML = "";
        if(!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center px-4 py-2">No leaves applied.</td></tr>`;
            return;
        }
        // filter by selected year
        const yearSel = document.getElementById('yearFilter');
        const selectedYear = yearSel ? parseInt(yearSel.value, 10) : (new Date()).getFullYear();
        data = data.filter(lv => {
            const d = lv.from_date || lv.start_date || null;
            if (!d) return false;
            const yr = new Date(d).getFullYear();
            return yr === selectedYear;
        });
        if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center px-4 py-2">No leaves for ${selectedYear}.</td></tr>`;
            return;
        }
        data.forEach(lv => {
            const viewLink = lv.leave_letter ? `<a href="../uploads/leave_letters/${lv.leave_letter}" target="_blank" class="text-indigo-600 hover:underline">View</a>` : '';
            tbody.innerHTML += `
            <tr class="text-center hover:bg-gray-50">
                <td class="px-4 py-2 border">${lv.employee_name}</td>
                <td class="px-4 py-2 border">${lv.leave_type}</td>
                <td class="px-4 py-2 border">${lv.from_date}</td>
                <td class="px-4 py-2 border">${lv.to_date}</td>
                <td class="px-4 py-2 border">${lv.status}</td>
                <td class="px-4 py-2 border">
                    <div class="flex items-center justify-center gap-2">
                        <button class="action-btn editBtn bg-blue-600 hover:bg-blue-700 text-white p-2 rounded" data-id="${lv.id}" title="Edit" aria-label="Edit">
                            <i class="bx bx-edit"></i>
                        </button>
                        <button class="action-btn deleteBtn bg-red-600 hover:bg-red-700 text-white p-2 rounded" data-id="${lv.id}" title="Delete" aria-label="Delete">
                            <i class="bx bx-trash"></i>
                        </button>
                        ${viewLink ? `<a href="../uploads/leave_letters/${lv.leave_letter}" target="_blank" class="action-btn bg-indigo-100 hover:bg-indigo-200 text-indigo-600 p-2 rounded" title="View Leave Letter" aria-label="View Leave Letter"><i class="bx bx-file"></i></a>` : ''}
                    </div>
                </td>
            </tr>
            `;
        });

        // Edit leave
        document.querySelectorAll(".editBtn").forEach(btn => {
            btn.addEventListener("click", () => {
                const id = btn.dataset.id;
                fetch(`../api/leaves.php?id=${id}`)
                .then(res => res.json())
                .then(lv => {
                    document.getElementById("leaveId").value = lv.id;
                    // set select to employee_id returned by API
                    document.getElementById("employee").value = lv.employee_id || '';
                    document.getElementById("leaveType").value = lv.leave_type;
                    document.getElementById("fromDate").value = lv.from_date;
                    document.getElementById("toDate").value = lv.to_date;
                    // set status dropdown if present
                    const statusEl = document.getElementById('status');
                    if (statusEl) statusEl.value = lv.status || 'Pending';
                    modal.classList.remove("hidden");
                });
            });
        });

        // Delete leave
        document.querySelectorAll(".deleteBtn").forEach(btn => {
            btn.addEventListener("click", () => {
                if(confirm("Are you sure you want to delete this leave?")) {
                    fetch(`../api/leaves.php?id=${btn.dataset.id}`, { method: "DELETE" })
                    .then(res => res.json())
                    .then(data => {
                        // prefer 'message' or 'ok' flag, fallback to 'error'
                        if (data.ok || data.message) alert(data.message || 'Deleted');
                        else alert(data.error || 'Delete failed');
                        loadLeaves();
                    });
                }
            });
        });
    }).catch(err => {
        document.getElementById("leavesTableBody").innerHTML = `<tr><td colspan="6" class="text-center px-4 py-2">Error loading leaves.</td></tr>`;
        console.error('loadLeaves error', err);
    });
}

// Populate year filter with a sliding window and default to current year
function populateYearFilter() {
    const sel = document.getElementById('yearFilter');
    if (!sel) return;
    const now = new Date();
    const currentYear = now.getFullYear();
    // show range currentYear-3 .. currentYear+1
    const start = currentYear - 3;
    const end = currentYear + 1;
    sel.innerHTML = '';
    for (let y = end; y >= start; y--) {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        if (y === currentYear) opt.selected = true;
        sel.appendChild(opt);
    }
    sel.addEventListener('change', () => loadLeaves());
}

// Load employees into the select dropdown
function loadEmployees() {
    fetch('../api/employees.php')
    .then(res => res.json())
    .then(data => {
        const sel = document.getElementById('employee');
        sel.innerHTML = '<option value="">Select Employee</option>';
        if (!Array.isArray(data)) return;
        data.forEach(emp => {
            const opt = document.createElement('option');
            opt.value = emp.id;
            opt.textContent = emp.name;
            sel.appendChild(opt);
        });
    }).catch(err => console.error('loadEmployees error', err));
}

// Apply / Edit leave
document.getElementById("applyLeaveForm").addEventListener("submit", function(e){
    e.preventDefault();
    const id = document.getElementById("leaveId").value;
    const employee = document.getElementById("employee").value; // this is employee_id now
    const leaveType = document.getElementById("leaveType").value;
    const fromDate = document.getElementById("fromDate").value;
    const toDate = document.getElementById("toDate").value;
    const msg = document.getElementById("msg");

    if(!employee || !leaveType || !fromDate || !toDate){
        msg.innerText = "Please fill in all fields.";
        msg.classList.remove("text-green-600");
        msg.classList.add("text-red-600");
        return;
    }

    // When updating with a file we must use POST + _method=PUT because browsers don't send multipart bodies for true PUT
    let method = id ? "PUT" : "POST";
    let url = id ? `../api/leaves.php?id=${id}` : "../api/leaves.php";

    const status = document.getElementById('status') ? document.getElementById('status').value : 'Pending';
    // if a file is chosen, send FormData (multipart/form-data)
    const fileInput = document.getElementById('leaveLetter');
    let fetchOptions = { method };
    if (fileInput && fileInput.files && fileInput.files.length > 0) {
        const fd = new FormData();
        fd.append('employee_id', employee);
        fd.append('leave_type', leaveType);
        fd.append('from_date', fromDate);
        fd.append('to_date', toDate);
        fd.append('status', status);
        fd.append('leave_letter', fileInput.files[0]);
        // if editing, use POST with method override so API can accept multipart
        if (id) { fd.append('_method', 'PUT'); method = 'POST'; url = `../api/leaves.php?id=${id}`; }
        fetchOptions.body = fd;
    } else {
        fetchOptions.headers = { "Content-Type": "application/json" };
        fetchOptions.body = JSON.stringify({ employee_id: employee, leave_type: leaveType, from_date: fromDate, to_date: toDate, status: status });
    }

    fetchOptions.method = method;
    fetch(url, fetchOptions)
    .then(res => res.json())
    .then(data => {
        if (data.id || data.ok) {
            msg.innerText = data.message || 'Saved successfully.';
            msg.classList.remove("text-red-600");
            msg.classList.add("text-green-600");
            this.reset();
            // reset status to Pending after reset
            const statusEl2 = document.getElementById('status'); if (statusEl2) statusEl2.value = 'Pending';
            modal.classList.add("hidden");
            loadLeaves();
        } else if(data.error){
            msg.innerText = data.error;
            msg.classList.remove("text-green-600");
            msg.classList.add("text-red-600");
        } else {
            msg.innerText = 'Unexpected response from server.';
            msg.classList.remove("text-green-600");
            msg.classList.add("text-red-600");
            console.log('unexpected:', data);
        }
    }).catch(err => {
        msg.innerText = 'Network or server error.';
        msg.classList.remove("text-green-600");
        msg.classList.add("text-red-600");
        console.error('submit error', err);
    });
});

// Initial load
loadEmployees();
populateYearFilter();
loadLeaves();
</script>