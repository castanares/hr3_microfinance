<?php
include '../layout/Layout.php';

$today = date("F d, Y");

$children = <<<HTML
<div class="p-6 bg-gray-50 min-h-screen">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-2">
            <i class="bx bx-time-five text-indigo-600 text-3xl"></i>
            <h1 class="text-2xl font-bold text-gray-800">Employees Attendance Log</h1>
        </div>
        <span class="text-sm text-gray-500">Updated: {$today}</span>
    </div>

    <div class="mb-4 flex items-center justify-between">
        <form id="rfidForm" class="flex items-center gap-2">
            <input type="text" id="rfidInput" class="bg-indigo-50 border border-indigo-300 px-4 py-2 rounded-lg shadow focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Scan RFID to view this employee's attendance (current month)" autofocus>
            <button type="submit" id="submitRfidBtn" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Lookup</button>
        </form>
        <div class="flex items-center gap-4">
            <div id="currentEmployee" class="text-sm text-gray-700">No employee selected</div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white p-6 rounded-xl shadow overflow-x-auto">
        <div style="max-height:65vh; overflow:auto;">
        <table class="w-full table-auto border-collapse border border-gray-200 text-left">
            <thead class="bg-gray-100 sticky top-0 z-10">
                <tr>
                    <th class="px-4 py-2 border border-gray-200">#</th>
                    <th class="px-4 py-2 border border-gray-200">Employee Name</th>
                    <th class="px-4 py-2 border border-gray-200">Shift</th>
                    <th class="px-4 py-2 border border-gray-200">Date</th>
                    <th class="px-4 py-2 border border-gray-200">Clock In</th>
                    <th class="px-4 py-2 border border-gray-200">Clock In Status</th>
                    <th class="px-4 py-2 border border-gray-200">Clock Out</th>
                    <th class="px-4 py-2 border border-gray-200">Status</th>
                </tr>
            </thead>
            <tbody id="attendanceTableBody">
                <tr><td colspan="8" class="text-center py-4 text-gray-500">Scan an RFID to view this employee's attendance for the current month.</td></tr>
            </tbody>
        </table>
        </div>
    </div>

    <div id="currentTime" class="text-center text-lg font-semibold text-gray-700 mt-4"></div>

</div>

<!-- Alert / Toast container -->
<div id="alertContainer" class="fixed top-5 right-5 z-50 space-y-2"></div>
HTML;

Layout($children);
?>

<script>
// Auto focus RFID input on page load
try {
    var rfidInput = document.getElementById('rfidInput');
    if (rfidInput) rfidInput.focus();
} catch (e) {}

document.addEventListener('DOMContentLoaded', function() {
    const API_URL = "../api/attendance.php";
    const EMPLOYEES_API = "../api/employees.php";
    const tableBody = document.getElementById("attendanceTableBody");
    const rfidForm = document.getElementById('rfidForm');
    const rfidInput = document.getElementById('rfidInput');
    const currentEmployeeEl = document.getElementById('currentEmployee');

    function showAlert(message, type = 'info', timeout = 4000) {
        const container = document.getElementById('alertContainer');
        const id = 'alert-' + Date.now();
        const bg = type === 'success' ? 'bg-green-50' : type === 'error' ? 'bg-red-50' : type === 'warning' ? 'bg-yellow-50' : 'bg-indigo-50';
        const border = type === 'success' ? 'border-green-300' : type === 'error' ? 'border-red-300' : type === 'warning' ? 'border-yellow-300' : 'border-indigo-300';
        const text = type === 'success' ? 'text-green-700' : type === 'error' ? 'text-red-700' : type === 'warning' ? 'text-yellow-700' : 'text-indigo-700';
        const html = `\n            <div id="${id}" class="w-96 ${bg} ${border} border px-4 py-3 rounded shadow flex items-start justify-between">\n                <div class="flex items-start gap-3">\n                    <div class="flex-shrink-0">\n                        <svg class="w-6 h-6 ${text}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">\n                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d=\"M13 16h-1v-4h-1m1-4h.01M12 7v.01\"></path>\n                        </svg>\n                    </div>\n                    <div class="text-sm ${text}">${message}</div>\n                </div>\n                <button aria-label="Close alert" class="text-gray-500 hover:text-gray-700 ml-4" onclick="(function(){const el=document.getElementById('${id}'); if(el) el.remove();})()">&times;</button>\n            </div>\n        `;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        container.appendChild(wrapper.firstElementChild);
        if (timeout > 0) setTimeout(() => { const el = document.getElementById(id); if (el) el.remove(); }, timeout);
    }

    function formatDateNice(dateStr) {
        if (!dateStr) return '';
        const [y,m,d] = dateStr.split('-');
        const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return `${monthNames[parseInt(m)-1]} ${parseInt(d)}, ${y}`;
    }

    function formatTime(timeStr) {
        if (!timeStr) return '-';
        const parts = timeStr.split(':');
        if (parts.length < 2) return timeStr;
        let hour = parseInt(parts[0],10);
        const minute = String(parts[1]).padStart(2,'0');
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minute} ${ampm}`;
    }

    function renderAttendanceList(employee, rows){
        tableBody.innerHTML = '';
        if (!rows || rows.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-gray-500">No attendance records found for this month.</td></tr>';
            return;
        }
        let cnt = 0;
        rows.forEach(row => {
            cnt++;
            const formattedDate = row.date ? formatDateNice(row.date) : '';
            const clockIn = row.time_in ? formatTime((row.time_in+'').split(' ')[1] || row.time_in) : '-';
            const clockOut = row.time_out ? formatTime((row.time_out+'').split(' ')[1] || row.time_out) : '-';
            const clockInStatus = row.Clock_In_Status || row.status || '-';
            const clockInStatusClass = clockInStatus === 'Late' ? 'text-yellow-600' : clockInStatus === 'On Time' ? 'text-green-600' : '';
            const overallStatus = row.status_clock_out || row.status || '';
            const overallClass = overallStatus === 'Present' ? 'text-green-600' : overallStatus === 'Late' ? 'text-yellow-600' : overallStatus === 'Absent' ? 'text-red-600' : '';

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50';
            tr.innerHTML = `
                <td class="px-4 py-2 border border-gray-200">${cnt}</td>
                <td class="px-4 py-2 border border-gray-200">${employee.name || employee.full_name || employee.employee_name || ''}</td>
                <td class="px-4 py-2 border border-gray-200">${row.shift || '-'}</td>
                <td class="px-4 py-2 border border-gray-200">${formattedDate}</td>
                <td class="px-4 py-2 border border-gray-200">${clockIn}</td>
                <td class="px-4 py-2 border border-gray-200 font-semibold ${clockInStatusClass}">${clockInStatus}</td>
                <td class="px-4 py-2 border border-gray-200">${clockOut}</td>
                <td class="px-4 py-2 border border-gray-200 font-semibold ${overallClass}">${overallStatus}</td>
            `;
            tableBody.appendChild(tr);
        });
    }

    async function lookupEmployeeByRfid(rfid){
        const res = await fetch(`${EMPLOYEES_API}?rfid=${encodeURIComponent(rfid)}`);
        const data = await res.json();
        return Array.isArray(data) && data.length ? data[0] : null;
    }

    // Fetch attendance for employee for a given month (YYYY-MM)
    async function fetchAttendanceForMonth(employeeId, month) {
        // Ask the API for the employee/month, but defensively filter client-side because APIs
        // sometimes return extra rows or different field names.
        const res = await fetch(`${API_URL}?employee_id=${encodeURIComponent(employeeId)}&month=${encodeURIComponent(month)}`);
        const data = await res.json();
        const arr = Array.isArray(data) ? data : [];

        const empIdStr = String(employeeId);

        return arr.filter(r => {
            try {
                // Match employee id across several potential field names
                const idCandidates = [r.employee_id, r.emp_id, r.employeeId, r.id, r.user_id, r.empid];
                const matchesId = idCandidates.some(v => v !== undefined && v !== null && String(v) === empIdStr);
                if (!matchesId) return false;

                // Find a date-like field from common names and values (handles date or datetime)
                const dateCandidates = [r.date, r.attendance_date, r.att_date, r.created_at, r.time_in, r.time_out];
                const dateRaw = dateCandidates.find(v => v !== undefined && v !== null && (typeof v === 'string' || typeof v === 'number'));
                if (!dateRaw) return false;
                const dateStr = String(dateRaw);

                // Extract YYYY-MM using a regex that accepts '-' or '/'
                const m = dateStr.match(/(\d{4})[-\/](\d{2})/);
                if (!m) return false;
                const yymm = `${m[1]}-${m[2]}`;
                return yymm === month;
            } catch (e) {
                return false;
            }
        });
    }

    async function handleRfidSubmit(e){
        e.preventDefault();
        const rfid = rfidInput.value.trim();
        if (!rfid) { showAlert('Please enter an RFID.', 'warning'); return; }
        try {
            const emp = await lookupEmployeeByRfid(rfid);
            if (!emp) { showAlert('Employee not found for this RFID.', 'error'); return; }
            currentEmployeeEl.textContent = `Selected: ${emp.name || emp.full_name || emp.employee_name || emp.firstname || ''}`;
            // Determine current month YYYY-MM
            const now = new Date();
            const month = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;
            const rows = await fetchAttendanceForMonth(emp.id || emp.employee_id || emp.ID, month);
            renderAttendanceList(emp, rows);
            rfidInput.value = '';
            rfidInput.focus();
        } catch (err) {
            console.error('RFID lookup error', err);
            showAlert('Error fetching attendance.', 'error');
        }
    }

    rfidForm.addEventListener('submit', handleRfidSubmit);

    function updateCurrentTime() {
        const now = new Date();
        document.getElementById('currentTime').innerText = now.toLocaleTimeString();
    }
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);
});
</script>