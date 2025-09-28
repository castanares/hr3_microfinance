<?php
include '../layout/Layout.php';

date_default_timezone_set('Asia/Manila');

// Sample leave applications (replace with DB queries)
// table will be populated by JS from the API. Keep server-rendered children minimal.
$children = '
<div class="p-6 bg-gray-50 min-h-screen">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2">
            <i class="bx bx-calendar-check text-indigo-600 text-3xl"></i>
            <h1 class="text-3xl font-bold text-gray-800">Leave History</h1>
        </div>
        <span class="text-sm text-gray-500">Today: ' . date("F d, Y") . '</span>
    </div>

    <!-- Filter & Search -->
    <div class="flex flex-wrap items-center justify-between mb-4  mx-auto gap-2">
        <select id="statusFilter" class="border rounded-lg p-2">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
        </select>
        <input type="text" id="searchInput" placeholder="Search by employee" class="border rounded-lg p-2 flex-1">
    </div>

    <!-- Leave History Table -->
    <div class="overflow-x-auto bg-white rounded-xl shadow p-4 max-w-10xl mx-auto">
        <table id="leaveTable" class="min-w-full border border-gray-200">
            <thead>
                <tr class="bg-gray-100 text-center">
                    <th class="px-4 py-2 border">Employee</th>
                    <th class="px-4 py-2 border">Leave Type</th>
                    <th class="px-4 py-2 border">From</th>
                    <th class="px-4 py-2 border">To</th>
                    <th class="px-4 py-2 border">Status</th>
                    <th class="px-4 py-2 border">Document</th>
                </tr>
            </thead>
            <tbody id="leaveTableBody">
                <tr><td colspan="6" class="text-center px-4 py-2">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

';

Layout($children);
?>

<script>
// Fetch all leaves from API and populate the table. Read-only view.
function loadLeaveHistory() {
    fetch('../api/leaves.php')
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById('leaveTableBody');
        tbody.innerHTML = '';
        if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center px-4 py-2">No leaves found.</td></tr>`;
            return;
        }
        data.forEach(lv => {
            const docLink = lv.leave_letter ? `<a href="../uploads/leave_letters/${lv.leave_letter}" target="_blank" class="text-indigo-600 hover:underline">View</a>` : '';
            tbody.innerHTML += `
                <tr class="text-center hover:bg-gray-50">
                    <td class="px-4 py-2 border">${lv.employee_name || ''}</td>
                    <td class="px-4 py-2 border">${lv.leave_type || lv.reason || ''}</td>
                    <td class="px-4 py-2 border">${lv.from_date || lv.start_date || ''}</td>
                    <td class="px-4 py-2 border">${lv.to_date || lv.end_date || ''}</td>
                    <td class="px-4 py-2 border">${lv.status || ''}</td>
                    <td class="px-4 py-2 border">${docLink}</td>
                </tr>
            `;
        });

        // Apply client-side filters (status + name search)
        applyFilters();
    }).catch(err => {
        document.getElementById('leaveTableBody').innerHTML = `<tr><td colspan="6" class="text-center px-4 py-2">Error loading leaves.</td></tr>`;
        console.error('loadLeaveHistory error', err);
    });
}

// Filter and search helpers
function applyFilters() {
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const search = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#leaveTable tbody tr');
    rows.forEach(row => {
        const status = (row.cells[4] && row.cells[4].innerText.toLowerCase()) || '';
        const employee = (row.cells[0] && row.cells[0].innerText.toLowerCase()) || '';
        const matchStatus = statusFilter === '' || status === statusFilter;
        const matchSearch = employee.includes(search);
        row.style.display = (matchStatus && matchSearch) ? '' : 'none';
    });
}

document.getElementById('statusFilter').addEventListener('change', applyFilters);
document.getElementById('searchInput').addEventListener('input', applyFilters);

// Initial load
loadLeaveHistory();
</script>