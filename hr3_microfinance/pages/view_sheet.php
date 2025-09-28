<?php
include '../layout/Layout.php';
date_default_timezone_set('Asia/Manila');

 $children = <<<'HTML'
<div class="p-6 bg-gray-50 min-h-screen">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2">
            <i class="bx bx-time text-yellow-500 text-3xl"></i>
            <h1 class="text-3xl font-bold text-gray-800">Attendance Records</h1>
        </div>
        <span class="text-sm text-gray-500" id="lastUpdated">Updated: {date}</span>
    </div>

    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <label for="filterDate" class="text-sm text-gray-600">Filter by date:</label>
            <input type="date" id="filterDate" class="border rounded-lg p-2" />
            <button id="applyDateFilter" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Filter</button>
            <button id="clearDateFilter" class="bg-gray-200 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-300">Clear</button>
            <button id="exportBtn" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Export</button>
        </div>
        <div class="text-sm text-gray-500">Showing all attendance — sorted with today's records first</div>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
        <div style="max-height:70vh; overflow:auto;">
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
            <tbody id="attendanceBody">
                <tr><td colspan="8" class="text-center py-4">Loading...</td></tr>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const API_URL = "../api/attendance.php";
    const tableBody = document.getElementById('attendanceBody');
    const filterDateEl = document.getElementById('filterDate');
    const applyBtn = document.getElementById('applyDateFilter');
    const clearBtn = document.getElementById('clearDateFilter');
    const exportBtn = document.getElementById('exportBtn');

    // Keep the currently visible rows (after filtering/sorting) for export
    let currentData = [];

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        try {
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
        } catch(e) { return dateStr; }
    }

    function formatTime(timeStr) {
        if (!timeStr) return '-';
        // Try HH:MM:SS or SQL datetime
        if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}/.test(timeStr)) {
            const iso = timeStr.replace(' ', 'T');
            const d = new Date(iso);
            if (!isNaN(d)) return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(timeStr)) {
            const parts = timeStr.split(':');
            let h = parseInt(parts[0],10);
            const m = parts[1];
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return `${h}:${m} ${ampm}`;
        }
        return timeStr;
    }

    async function loadAttendance() {
        try {
            const res = await fetch(API_URL);
            const data = await res.json();
            if (!Array.isArray(data)) {
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center py-3">No attendance data available.</td></tr>';
                return;
            }

            const today = new Date().toISOString().split('T')[0];

            // Sort: today's records first, then by date descending
            data.sort((a,b) => {
                if (a.date === today && b.date !== today) return -1;
                if (b.date === today && a.date !== today) return 1;
                // Compare date strings (ISO) for descending
                if (a.date === b.date) return 0;
                return (b.date || '').localeCompare(a.date || '');
            });

            const filterDate = filterDateEl.value || null;
            tableBody.innerHTML = '';
            // Build visible/filtered list from the already-sorted data
            const visible = data.filter(row => !filterDate || row.date === filterDate);
            currentData = visible.slice(); // copy for export
            if (visible.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center py-3">No attendance records for selected date.</td></tr>';
            } else {
                visible.forEach((row, idx) => {
                    const cnt = idx + 1;
                    const clockIn = row.time_in ? formatTime(row.time_in) : '-';
                    const clockOut = row.time_out ? formatTime(row.time_out) : '-';
                    const clockInStatus = row.Clock_In_Status || row.status || '-';
                    const statusOut = row.status_clock_out || row.status || '';
                    const clockInClass = clockInStatus === 'Late' ? 'text-yellow-600' : clockInStatus === 'On Time' ? 'text-green-600' : '';
                    const statusOutClass = statusOut === 'Present' || statusOut === 'On Time' ? 'text-green-600' : statusOut === 'Late' ? 'text-yellow-600' : 'text-red-600';

                    const tr = document.createElement('tr');
                    tr.classList.add('hover:bg-gray-50');
                    tr.innerHTML = `
                        <td class="px-4 py-2 border border-gray-200">${cnt}</td>
                        <td class="px-4 py-2 border border-gray-200">${row.employee_name || row.name || '-'}</td>
                        <td class="px-4 py-2 border border-gray-200">${row.shift || '-'}</td>
                        <td class="px-4 py-2 border border-gray-200">${formatDate(row.date)}</td>
                        <td class="px-4 py-2 border border-gray-200">${clockIn}</td>
                        <td class="px-4 py-2 border border-gray-200 ${clockInClass}">${clockInStatus}</td>
                        <td class="px-4 py-2 border border-gray-200">${clockOut}</td>
                        <td class="px-4 py-2 border border-gray-200 font-semibold ${statusOutClass}">${statusOut}</td>
                    `;
                    tableBody.appendChild(tr);
                });
            }

            
        } catch (err) {
            console.error('Error loading attendance:', err);
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center py-3">Error loading attendance.</td></tr>';
        }
    }

    function exportCSV() {
        if (!currentData || currentData.length === 0) {
            alert('No data to export for the selected date.');
            return;
        }

        const headers = ['#', 'Employee Name', 'Shift', 'Date', 'Clock In', 'Clock In Status', 'Clock Out', 'Status'];
        const rows = [headers];

        currentData.forEach((row, idx) => {
            // Export the ISO date (YYYY-MM-DD) to avoid Excel showing #### when column is narrow
            const date = row.date || formatDate(row.date);
            // Prefix with apostrophe to force Excel to treat the value as text (hidden in cell display)
            const dateText = "'" + date;
            const clockIn = row.time_in ? formatTime(row.time_in) : '-';
            const clockOut = row.time_out ? formatTime(row.time_out) : '-';
            const clockInStatus = row.Clock_In_Status || row.status || '-';
            const statusOut = row.status_clock_out || row.status || '';
            rows.push([
                String(idx + 1),
                row.employee_name || row.name || '-',
                row.shift || '-',
                dateText,
                clockIn,
                clockInStatus,
                clockOut,
                statusOut
            ]);
        });

    const csvContent = rows.map(r => r.map(cell => '"' + String(cell).replace(/"/g, '""') + '"').join(',')).join('\r\n');

    // Prepend UTF-8 BOM to help Excel on Windows open UTF-8 CSV correctly
    const bom = '\uFEFF';
    const blob = new Blob([bom + csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        const filenameDate = filterDateEl.value || new Date().toISOString().split('T')[0];
        a.href = url;
        a.download = `attendance_${filenameDate}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    applyBtn.addEventListener('click', () => loadAttendance());
    clearBtn.addEventListener('click', () => { filterDateEl.value = ''; loadAttendance(); });
    exportBtn.addEventListener('click', exportCSV);

    loadAttendance();
});
</script>
HTML;

// Inject current date into lastUpdated
$children = str_replace('{date}', date('F d, Y'), $children);

Layout($children);
?>
