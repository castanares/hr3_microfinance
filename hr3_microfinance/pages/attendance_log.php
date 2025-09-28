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
            // Normalize shift response to an array for consistent handling
            let shiftsToday = Array.isArray(shiftData) ? shiftData : (shiftData ? [shiftData] : []);
            // Filter shifts to only those that belong to this employee (avoid API returning unrelated shifts)
            shiftsToday = shiftsToday.filter(s => {
                if (!s) return false;
                if ('employee_id' in s) return String(s.employee_id) === String(employee.id);
                // If shift rows don't include employee_id, keep them (server might omit employee_id sometimes)
                return true;
            });
            // If no shift today for this employee, show explicit message and stop (avoid later 'Did not clock in' confusion)
            if (!shiftsToday || shiftsToday.length === 0) {
                showAlert('No shift assigned for today.', 'warning');
                prepareNextScan();
                return;
            }
            const shift = shiftsToday[0]; // Use the first shift as the primary

            // Fetch existing attendance for today (match by employee and date; server will auto-match shift)
            const attRes = await fetch(`${API_URL}?employee_id=${employee.id}&date=${currentDate}`);
            const attData = await attRes.json();

            // New guard: if any attendance record for today already has both time_in and time_out,
            // block further scans (employee already completed attendance for today).
            try {
                if (Array.isArray(attData)) {
                    // If any record for today is marked absent, block and inform the user.
                    const anyAbsentToday = attData.some(a => {
                        const st = (a.status || a.status_clock_out || a.Clock_In_Status || '').toString().toLowerCase();
                        return st === 'absent' && String(a.date || '') === currentDate;
                    });
                    if (anyAbsentToday) {
                        showAlert('Employee already marked absent for today.', 'error', 5000);
                        prepareNextScan();
                        return;
                    }
                    const anyCompleted = attData.some(a => a.time_in && a.time_out && String(a.date || '') === currentDate);
                    if (anyCompleted) {
                        showAlert('Employee already clocked out for today.', 'warning', 5000);
                        prepareNextScan();
                        return;
                    }
                } else if (attData && attData.time_in && attData.time_out && attData.date === currentDate) {
                    showAlert('Employee already clocked out for today.', 'warning', 5000);
                    prepareNextScan();
                    return;
                }
                // Also handle single-object absent response
                if (!Array.isArray(attData) && attData) {
                    const stSingle = (attData.status || attData.status_clock_out || attData.Clock_In_Status || '').toString().toLowerCase();
                    if (stSingle === 'absent' && attData.date === currentDate) {
                        showAlert('Employee already marked absent for today.', 'error', 5000);
                        prepareNextScan();
                        return;
                    }
                }
            } catch (e) {
                console.warn('Error checking completed attendance:', e);
            }
            // Find attendance for this specific shift name (server may prefer its own shift name)
            const serverShiftName = shift.shift_name || shift.shift || shift.name || null;
            let existingAtt = null;
            if (Array.isArray(attData)) {
                existingAtt = attData.find(a => {
                    const aDate = (a.date || '').toString();
                    const aShift = (a.shift || a.shift_name || '').toString();
                    return aDate === currentDate && aShift === serverShiftName;
                }) || null;
            } else if (attData && attData.date && attData.employee_id == employee.id) {
                // Single object returned
                const aShift = (attData.shift || attData.shift_name || '').toString();
                if (attData.date === currentDate && aShift === serverShiftName) existingAtt = attData;
            }

            // If there is any open attendance (time_in && !time_out) for today, prefer it
            // This handles cases where shift names don't exactly match but an open attendance exists
            try {
                let openAtt = null;
                if (Array.isArray(attData)) {
                    openAtt = attData.find(a => a.time_in && !a.time_out && String(a.date || '') === currentDate) || null;
                } else if (attData && attData.time_in && !attData.time_out && attData.date === currentDate) {
                    openAtt = attData;
                }
                if (openAtt) {
                    existingAtt = openAtt;
                }
            } catch (e) { console.warn('Error detecting open attendance:', e); }

            // New: if employee has NO clock-in for today and the current time is already past any shift end time,
            // show 'Did not clock in for today' and block the scan (prevents creating records for yesterday).
            try {
                const nowTs = (new Date()).getTime();
                // Determine if there is any clock-in today
                const hasClockInToday = Array.isArray(attData)
                    ? attData.some(a => a.time_in && String(a.date || '') === currentDate)
                    : (attData && attData.time_in && attData.date === currentDate);
                if (!hasClockInToday) {
                    // Determine lateness relative to shift START times.
                    // Rules:
                    // - If now is >= start + 120 minutes for ALL today's shifts => treat as 'Did not clock in for today' and block.
                    // - If delta <= 15 minutes (grace) for the nearest shift => allow (will be On Time).
                    // - If 15 < delta < 120 => allow but warn that it's Late.
                    const nowDate = new Date();
                    let anyAllowable = false;
                    let nearestDeltaMin = null;
                    for (const s of shiftsToday) {
                        const startRaw = s.start_time || s.time_start || s.start || s.time_start || null;
                        if (!startRaw) continue;
                        const startDateObj = parseShiftTimeToDate(startRaw, nowDate);
                        if (!startDateObj) continue;
                        const deltaMin = Math.round((nowDate.getTime() - startDateObj.getTime()) / 60000);
                        // track nearest (smallest non-negative) delta for messaging
                        if (nearestDeltaMin === null || Math.abs(deltaMin) < Math.abs(nearestDeltaMin)) nearestDeltaMin = deltaMin;
                        // If now is less than start+120min, allow this shift (user can still clock in)
                        if (deltaMin < 120) {
                            anyAllowable = true;
                        }
                    }

                    if (!anyAllowable) {
                        // All shifts are 2+ hours past their start => considered missed
                        showAlert('Did not clock in for today.', 'error', 5000);
                        prepareNextScan();
                        return;
                    }

                    // If we reach here, at least one shift is within the 2-hour window.
                    // Provide helpful messaging based on nearestDeltaMin (if available).
                    // if (nearestDeltaMin !== null) {
                    //     if (nearestDeltaMin <= 15) {
                    //         // Within grace period — treat as on time
                    //         showAlert('Within grace period — will be marked On Time.', 'info', 3000);
                    //     } else if (nearestDeltaMin > 15 && nearestDeltaMin < 120) {
                    //         showAlert('You are late by ' + nearestDeltaMin + ' minutes — will be marked Late.', 'warning', 4000);
                    //     }
                    // }
                }
            } catch (e) {
                console.warn('Error checking missed clock-in:', e);
            }

            // If there's any open attendance today (time_in && !time_out), allow the scan to perform clock-out
            // instead of blocking. We'll attach the open attendance id to the payload later so the server updates it.
            // Provide helpful messaging depending on whether the shift end could be parsed.
            try {
                let openPresent = false;
                if (Array.isArray(attData)) {
                    openPresent = attData.some(a => a.time_in && !a.time_out && String(a.date || '') === currentDate);
                } else if (attData && attData.time_in && !attData.time_out && attData.date === currentDate) {
                    openPresent = true;
                }
                if (openPresent) {
                    const shiftEndRaw = shift.end_time || shift.time_end || shift.end || null;
                    let endDateObj = null;
                    if (shiftEndRaw) {
                        endDateObj = parseShiftTimeToDate(shiftEndRaw, new Date());
                    }
                    const now = new Date();
                    if (endDateObj) {
                        if (now < endDateObj) {
                            // Shift hasn't ended yet, but allow immediate clock-out (force). Inform user.
                            showAlert('Employee already clocked in today but shift not yet ended — recording clock-out now.', 'warning', 5000);
                        } 
                        // else {
                        //     // Shift end passed — normal clock-out
                        //     showAlert('Recording clock-out for open attendance.', 'info', 3000);
                        // }
                    } else {
                        // Could not determine shift end — allow clock-out but warn admin may need to review
                        showAlert('Employee already clocked in today. Shift end time unknown — recording clock-out. Contact admin if this is incorrect.', 'warning', 6000);
                    }
                    // Do NOT block; let later logic attach attendance_id and POST to update the open record.
                }
            } catch (e) {
                console.warn('Error handling open attendance logic:', e);
            }

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
                // If there's any open attendance (time_in && !time_out) for today, attach its id so the server updates instead of inserting
                try {
                    if (Array.isArray(attData)) {
                        const open = attData.find(a => a.time_in && !a.time_out && String(a.date || '') === currentDate);
                        if (open && open.id) payload.attendance_id = open.id;
                    } else if (attData && attData.time_in && !attData.time_out && attData.date === currentDate && attData.id) {
                        payload.attendance_id = attData.id;
                    }
                } catch (e) { console.warn('Error attaching attendance_id:', e); }
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

            // (production) Do not log payload to console. Server response contains authoritative info.

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