<?php
include '../layout/Layout.php';

$children = '
<div class="p-6 bg-gray-50 min-h-screen">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-2">
            <i class="bx bx-time-five text-indigo-600 text-3xl"></i>
            <h1 class="text-2xl font-bold text-gray-800">Attendance Log</h1>
        </div>
        <span class="text-sm text-gray-500">Updated: ' . date("F d, Y") . '</span>
    </div>

  
    <div class="mb-4 flex items-center justify-between">
        <form id="rfidForm" class="flex items-center gap-2">
            <input type="text" id="rfidInput" class="bg-indigo-50 border border-indigo-300 px-4 py-2 rounded-lg shadow focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Scan RFID to add attendance" autofocus>
            <button type="submit" id="submitRfidBtn" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Submit</button>
        </form>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <label for="filterDate" class="text-sm text-gray-600">Date:</label>
                <input type="date" id="filterDate" class="border rounded-lg p-2" />
            </div>
            <button id="applyDateFilter" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Filter</button>
            <button id="clearDateFilter" class="bg-gray-200 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-300">Clear</button>
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
                <tr class="hover:bg-gray-50">
                   
                    <td class="px-4 py-2 border border-gray-200">Juan Dela Cruz</td>
                    <td class="px-4 py-2 border border-gray-200">Morning</td>
                    <td class="px-4 py-2 border border-gray-200">2025-09-10</td>
                    <td class="px-4 py-2 border border-gray-200">08:00</td>
                    <td class="px-4 py-2 border border-gray-200 font-semibold text-green-600">On Time</td>
                    <td class="px-4 py-2 border border-gray-200">05:00 PM</td>
                    <td class="px-4 py-2 border border-gray-200 font-semibold text-green-600">Present</td>
                </tr>
                <tr class="hover:bg-gray-50">
                  
                    <td class="px-4 py-2 border border-gray-200">Maria Santos</td>
                    <td class="px-4 py-2 border border-gray-200">Afternoon</td>
                    <td class="px-4 py-2 border border-gray-200">2025-09-10</td>
                    <td class="px-4 py-2 border border-gray-200">03:15 PM</td>
                    <td class="px-4 py-2 border border-gray-200 font-semibold text-yellow-600">Late</td>
                    <td class="px-4 py-2 border border-gray-200">10:05 PM</td>
                    <td class="px-4 py-2 border border-gray-200 font-semibold text-yellow-600">Late</td>
                </tr>
                <tr class="hover:bg-gray-50">
              
                    <td class="px-4 py-2 border border-gray-200">Pedro Reyes</td>
                    <td class="px-4 py-2 border border-gray-200">-</td>
                    <td class="px-4 py-2 border border-gray-200">2025-09-10</td>
                    <td class="px-4 py-2 border border-gray-200">-</td>
                    <td class="px-4 py-2 border border-gray-200">-</td>
                    <td class="px-4 py-2 border border-gray-200">-</td>
                    <td class="px-4 py-2 border border-gray-200 font-semibold text-red-600">Absent</td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>



    
    <div id="currentTime" class="text-center text-lg font-semibold text-gray-700 mt-4"></div>

    <!-- Employees with Shifts table removed -->





</div>

<!-- Alert / Toast container -->
<div id="alertContainer" class="fixed top-5 right-5 z-50 space-y-2"></div>




';


Layout($children);
?>

<script>
  // Auto focus RFID input on page load
try {
    var rfidInput = document.getElementById('rfidInput');
    if (rfidInput) rfidInput.focus();
} catch (e) {}
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Attendance filter & loader
    const API_URL = "../api/attendance.php";
    const SHIFTS_API = "../api/shifts.php";
    const EMPLOYEES_API = "../api/employees.php";
    const tableBody = document.getElementById("attendanceTableBody");
    const filterDateEl = document.getElementById('filterDate');
    const applyDateBtn = document.getElementById('applyDateFilter');
    const clearDateBtn = document.getElementById('clearDateFilter');
    const rfidForm = document.getElementById('rfidForm');
    const rfidInput = document.getElementById('rfidInput');

    function prepareNextScan() {
        try { rfidInput.value = ''; rfidInput.focus(); } catch(e) {}
    }

    function setDefaultDate(){
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth()+1).padStart(2,'0');
            const dd = String(today.getDate()).padStart(2,'0');
            filterDateEl.value = `${yyyy}-${mm}-${dd}`;
    }

    async function loadAttendance(){
        try {
            const [attendanceRes, shiftsRes] = await Promise.all([
                fetch(API_URL),
                fetch(SHIFTS_API)
            ]);
            const attendanceData = await attendanceRes.json();
            const shiftsData = await shiftsRes.json();

            const filterDate = filterDateEl.value || null;

            tableBody.innerHTML = "";
            let cnt = 0;
            attendanceData.forEach((row) => {
                const dateVal = row.date || '';
                if (filterDate && dateVal !== filterDate) return;
                cnt++;
                let formattedDate = '';
                if (row.date) {
                    const [y, m, d] = row.date.split('-');
                    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                    formattedDate = `${monthNames[parseInt(m) - 1]} ${parseInt(d)}, ${y}`;
                }
                let clockInTime = '-';
                if (row.time_in) {
                    const parts = row.time_in.split(' ');
                    let timePart = '';
                    if (parts.length === 2) {
                        timePart = parts[1];
                    } else if (parts.length === 1) {
                        timePart = parts[0];
                    }
                    if (timePart) {
                        const [h, m] = timePart.split(':');
                        const hour = parseInt(h);
                        const ampm = hour >= 12 ? 'PM' : 'AM';
                        const hour12 = hour % 12 || 12;
                        clockInTime = `${hour12}:${m} ${ampm}`;
                    }
                }
                let clockOutTime = '-';
                if (row.time_out) {
                    const parts = row.time_out.split(' ');
                    let timePart = '';
                    if (parts.length === 2) {
                        timePart = parts[1];
                    } else if (parts.length === 1) {
                        timePart = parts[0];
                    }
                    if (timePart) {
                        const [h, m] = timePart.split(':');
                        const hour = parseInt(h);
                        const ampm = hour >= 12 ? 'PM' : 'AM';
                        const hour12 = hour % 12 || 12;
                        clockOutTime = `${hour12}:${m} ${ampm}`;
                    }
                }
                const matchingShift = shiftsData.find(s => s.employee_id == row.employee_id && s.date === dateVal);
                const shiftName = matchingShift ? matchingShift.shift_name : row.shift || '-';
                const clockInStatus = row.Clock_In_Status || row.status || '-';
                let clockInStatusClass = '';
                if (String(clockInStatus).toLowerCase() === 'late') clockInStatusClass = 'text-yellow-600';
                else if (String(clockInStatus).toLowerCase() === 'on time') clockInStatusClass = 'text-green-600';
                else if (String(clockInStatus).toLowerCase() === 'absent') clockInStatusClass = 'text-red-600';
                
                const overallStatusVal = row.status_clock_out || row.status || '';
                let overallClass = '';
                if (String(overallStatusVal).toLowerCase() === 'present') overallClass = 'text-green-600';
                else if (String(overallStatusVal).toLowerCase() === 'late') overallClass = 'text-yellow-600';
                else if (String(overallStatusVal).toLowerCase() === 'absent') overallClass = 'text-red-600';
                const tr = document.createElement("tr");
                tr.classList.add("hover:bg-gray-50");
                tr.innerHTML = `
                    <td class="px-4 py-2 border border-gray-200">${cnt}</td>
                    <td class="px-4 py-2 border border-gray-200">${row.employee_name || ''}</td>
                    <td class="px-4 py-2 border border-gray-200">${shiftName}</td>
                    <td class="px-4 py-2 border border-gray-200">${formattedDate}</td>
                    <td class="px-4 py-2 border border-gray-200">${clockInTime}</td>
                    <td class="px-4 py-2 border border-gray-200 font-semibold ${clockInStatusClass}">${clockInStatus}</td>
                    <td class="px-4 py-2 border border-gray-200">${clockOutTime}</td>
                    <td class="px-4 py-2 border border-gray-200 font-semibold ${overallClass}">${overallStatusVal}</td>
                `;
                tableBody.appendChild(tr);
            });
            if(cnt === 0){
                    tableBody.innerHTML = '<tr><td colspan="8" class="text-center py-3">No attendance records for selected date.</td></tr>';
            }

            // Employees-with-shifts table removed from UI
        } catch (err) {
            console.error("Error loading attendance:", err);
        }
    }

    // Submit RFID
    // Styled alert helper
    function showAlert(message, type = 'info', timeout = 4000) {
        const container = document.getElementById('alertContainer');
        const id = 'alert-' + Date.now();
        const bg = type === 'success' ? 'bg-green-50' : type === 'error' ? 'bg-red-50' : type === 'warning' ? 'bg-yellow-50' : 'bg-indigo-50';
        const border = type === 'success' ? 'border-green-300' : type === 'error' ? 'border-red-300' : type === 'warning' ? 'border-yellow-300' : 'border-indigo-300';
        const text = type === 'success' ? 'text-green-700' : type === 'error' ? 'text-red-700' : type === 'warning' ? 'text-yellow-700' : 'text-indigo-700';
        const html = `\n            <div id="${id}" class="w-96 ${bg} ${border} border px-4 py-3 rounded shadow flex items-start justify-between">\n                <div class="flex items-start gap-3">\n                    <div class="flex-shrink-0">\n                        <svg class="w-6 h-6 ${text}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">\n                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d=\"M13 16h-1v-4h-1m1-4h.01M12 7v.01\"></path>\n                        </svg>\n                    </div>\n                    <div class="text-sm ${text}">${message}</div>\n                </div>\n                <button aria-label=\"Close alert\" class=\"text-gray-500 hover:text-gray-700 ml-4\" onclick=\"(function(){const el=document.getElementById('${id}'); if(el) el.remove();})()\">&times;</button>\n            </div>\n        `;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        container.appendChild(wrapper.firstElementChild);
        if (timeout > 0) setTimeout(() => { const el = document.getElementById(id); if (el) el.remove(); }, timeout);
    }

    async function submitRfid(e) {
        e.preventDefault();
        const rfid = rfidInput.value.trim();
        if (!rfid) {
            showAlert('Please enter an RFID.', 'warning');
            prepareNextScan();
            return;
        }

        try {
            // Fetch employee by RFID
            const empRes = await fetch(`${EMPLOYEES_API}?rfid=${encodeURIComponent(rfid)}`);
            const empData = await empRes.json();
            if (!empData || empData.length === 0) {
                showAlert('Employee not found for this RFID.', 'error');
                prepareNextScan();
                return;
            }
            const employee = empData[0]; // Assume first match

            // Get current date and time
            const now = new Date();
            const currentDate = now.toISOString().split('T')[0];
            const currentTime = now.toTimeString().split(' ')[0]; // HH:MM:SS
            const currentDateTime = currentDate + ' ' + currentTime;

            // Fetch shift for employee and date
            const shiftRes = await fetch(`${SHIFTS_API}?employee_id=${employee.id}&date=${currentDate}`);
            const shiftData = await shiftRes.json();
            const shift = shiftData[0]; // Assume one shift per day
            if (!shift) {
                showAlert('No shift assigned for today.', 'warning');
                prepareNextScan();
                return;
            }

            // Fetch existing attendance for today
            const attRes = await fetch(`${API_URL}?employee_id=${employee.id}&date=${currentDate}&shift=${shift.shift_name}`);
            const attData = await attRes.json();
            const existingAtt = attData[0]; // Assume one record per shift

            // If there's already both time_in and time_out recorded, block further scans
            if (existingAtt && existingAtt.time_in && existingAtt.time_out) {
                showAlert('Employee already clocked out for this shift.', 'warning');
                prepareNextScan();
                return;
            }

            let payload = {
                employee_id: employee.id,
                shift: shift.shift_name,
                rfid: employee.rfid,
                date: currentDate,
                // include client timezone if available (IANA tz string)
                client_tz: (typeof Intl !== 'undefined' && Intl.DateTimeFormat && Intl.DateTimeFormat().resolvedOptions) ? Intl.DateTimeFormat().resolvedOptions().timeZone : null
            };

            if (!existingAtt || !existingAtt.time_in) {
                // Clock In: do not send client-side statuses; let server set time_in and Clock_In_Status
            } else {
                // Clock Out: request server to record clock out. Client must not compute final status.
                const endDate = parseShiftTimeToDate(shift.end_time, now);
                if (endDate && now < endDate) {
                    // Don't block the user — allow the clock-out submit to proceed so the server
                    // can apply authoritative rules. Show a non-blocking warning instead.
                    showAlert('Shift not ended yet — forcing clock-out. Server will compute final status.', 'warning', 4000);
                    // continue without returning
                }
                // Let server set time_out/status
            }

            // Send to API
            const res = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            // API may return server_now/server_timezone for verification; you can inspect `data` in the console if needed
            if (data.success) {
                // Show success toast then perform full reload so the UI (and any server-side changes) are reflected
                showAlert('Attendance recorded successfully.', 'success', 2000);
                rfidInput.value = '';
                rfidInput.focus();
                setTimeout(() => { window.location.reload(); }, 800);
            } else {
                showAlert('Error: ' + (data.error || 'Unknown error'), 'error');
                // prepare for next scan
                rfidInput.value = '';
                rfidInput.focus();
            }
        } catch (err) {
            console.error('Error submitting RFID:', err);
            showAlert('Error submitting RFID.', 'error');
            rfidInput.value = '';
            rfidInput.focus();
        }
    }

    // Helper function to add minutes to time string
    function addMinutes(timeStr, minutes) {
        const [hours, mins] = timeStr.split(':').map(Number);
        const date = new Date();
        date.setHours(hours, mins + minutes, 0, 0);
        return date.toTimeString().split(' ')[0];
    }

    // Helper to parse a time string like "3:30 PM" or "15:30" into a Date object for today
    function parseTimeToDate(timeStr) {
        if (!timeStr) return null;
        // If format includes AM/PM
        const parts = timeStr.trim().split(' ');
        let hours = 0, minutes = 0;
        if (parts.length === 2) {
            const [hStr, mStr] = parts[0].split(':');
            hours = parseInt(hStr, 10);
            minutes = parseInt(mStr, 10);
            const ampm = parts[1].toUpperCase();
            if (ampm === 'PM' && hours !== 12) hours += 12;
            if (ampm === 'AM' && hours === 12) hours = 0;
        } else {
            // Assume 24-hour format HH:MM
            const [hStr, mStr] = parts[0].split(':');
            hours = parseInt(hStr, 10);
            minutes = parseInt(mStr, 10);
        }
        const d = new Date();
        d.setHours(hours, minutes, 0, 0);
        return d;
    }

    // Resolve AM/PM ambiguity for shift times that may be stored without AM/PM
    // e.g. "5:00" could be 5 AM or 5 PM — choose the candidate closest to now within 12 hours
    function parseShiftTimeToDate(timeStr, now) {
        if (!timeStr) return null;
        const raw = (timeStr || '').toString();
        const parsed = parseTimeToDate(raw);
        if (!parsed) return null;
        try {
            const hasAmPm = /am|pm/i.test(raw);
            if (!hasAmPm) {
                const candidates = [
                    parsed,
                    new Date(parsed.getTime() + 12 * 60 * 60 * 1000),
                    new Date(parsed.getTime() - 12 * 60 * 60 * 1000)
                ];
                let best = parsed;
                let bestDiff = Math.abs(now - parsed);
                candidates.forEach(c => {
                    const d = Math.abs(now - c);
                    if (d < bestDiff && d <= 12 * 60 * 60 * 1000) {
                        best = c;
                        bestDiff = d;
                    }
                });
                return best;
            }
        } catch (e) {
            console.warn('Error resolving shift time ambiguity', e);
        }
        return parsed;
    }

    // Helper function to convert time to 24-hour format
    function to24Hour(timeStr) {
        const parts = timeStr.split(' ');
        if (parts.length === 1) return timeStr; // already 24-hour
        const [h, m] = parts[0].split(':').map(Number);
        const ampm = parts[1].toUpperCase();
        let hour = h;
        if (ampm === 'PM' && h !== 12) hour += 12;
        if (ampm === 'AM' && h === 12) hour = 0;
        return `${hour.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}`;
    }

    // Helper function to format time to 12-hour. Accepts:
    // - ISO timestamps: 2025-09-25T17:00:00.000Z or 2025-09-25T17:00:00
    // - SQL datetimes: "YYYY-MM-DD HH:MM:SS"
    // - Plain times: "HH:MM" or "HH:MM:SS"
    function formatTime(timeStr) {
        if (!timeStr) return '-';
        let d = null;

        // If the string looks like an SQL datetime (YYYY-MM-DD HH:MM:SS), convert to ISO
        if (/^\d{4}-\d{2}-\d{2}\s+\d{1,2}:\d{2}(:\d{2})?/.test(timeStr)) {
            const iso = timeStr.replace(' ', 'T');
            d = new Date(iso);
            if (isNaN(d)) d = null;
        }

        // If it contains 'T' or 'Z', try parsing as ISO
        if (!d && (timeStr.indexOf('T') !== -1 || timeStr.indexOf('Z') !== -1)) {
            d = new Date(timeStr);
            if (isNaN(d)) d = null;
        }

        // Try plain time 'HH:MM' or 'HH:MM:SS'
        if (!d && /^(\d{1,2}):(\d{2})(:\d{2})?$/.test(timeStr)) {
            const parts = timeStr.split(':');
            const hour = parseInt(parts[0], 10);
            const minute = parseInt(parts[1], 10);
            const second = parts[2] ? parseInt(parts[2], 10) : 0;
            d = new Date();
            d.setHours(hour, minute, second, 0);
        }

        // Fallback: let Date try to parse
        if (!d) {
            const parsed = new Date(timeStr);
            if (!isNaN(parsed)) d = parsed;
        }

        if (!d || isNaN(d)) return '-';

        const hour = d.getHours();
        const minute = String(d.getMinutes()).padStart(2, '0');
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minute} ${ampm}`;
    }

    // Debug helper removed

    rfidForm.addEventListener('submit', submitRfid);
    // Fire-and-forget trigger: call mark_absent immediately when submit button is clicked
    try {
        const submitBtn = document.getElementById('submitRfidBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                try { fetch('../scripts/mark_absent.php', { method: 'GET', cache: 'no-cache' }).catch(()=>{}); } catch(e){}
            });
        }
    } catch(e) { console.warn('mark_absent immediate trigger setup failed', e); }

    applyDateBtn.addEventListener('click', () => loadAttendance());
    clearDateBtn.addEventListener('click', () => { filterDateEl.value = ''; loadAttendance(); });

    setDefaultDate();
    loadAttendance();

    function updateCurrentTime() {
        const now = new Date();
        document.getElementById('currentTime').innerText = now.toLocaleTimeString();
    }
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);
});
</script>