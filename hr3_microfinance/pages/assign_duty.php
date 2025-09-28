<?php
include '../layout/Layout.php';
date_default_timezone_set('Asia/Manila');

$children = '
<div class="p-6 bg-gray-50 h-[150%]">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-2">
            <i class="bx bx-calendar text-green-600 text-3xl"></i>
            <h1 class="text-3xl font-bold text-gray-800">Assign Duty</h1>
        </div>
        <span class="text-sm text-gray-500">Today: ' . date("F d, Y") . '</span>
    </div>


    <!-- Add Shift Button -->
    <div class="flex justify-end mb-6">
        <button id="openShiftModalBtn" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
            Add Shift
        </button>
    </div>

    <!-- Assign Shift Modal -->
    <div id="assignShiftModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow p-6 max-w-lg w-full relative">
            <button id="closeShiftModalBtn" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            <h2 class="text-2xl font-bold mb-4">Assign Shift</h2>
            <form id="assignShiftForm">
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="employee">Employee (Attendance)</label>
                    <select id="employee" class="w-full border rounded-lg p-2">
                        <option value="">Select Employee</option>
                        <option value="1">Juan Dela Cruz</option>
                        <option value="2">Maria Santos</option>
                        <option value="3">Pedro Reyes</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="shift">Shift Type</label>
                    <select id="shift" class="w-full border rounded-lg p-2">
                        <option value="">Select Shift</option>
                        <option value="Morning">Morning</option>
                        <option value="Afternoon">Afternoon</option>
                        <option value="Night">Night</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="date">Date</label>
                    <input type="date" id="date" class="w-full border rounded-lg p-2" value="' . date("Y-m-d") . '">
                </div>

                <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
                    Assign Shift
                </button>
            </form>
            <p id="msg" class="mt-4 font-semibold text-green-600"></p>
        </div>
    </div>

    <!-- Assigned Shifts Table -->
    <div class="bg-white p-10 rounded-xl shadow mt-10 overflow-x-auto w-full" style="max-width:100%;">
        <h2 class="text-xl font-bold mb-4">Assigned Shifts</h2>
        <table class="w-full table-auto border-collapse border border-gray-200 text-left">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 border border-gray-200">#</th>
                    <th class="px-4 py-2 border border-gray-200">Employee (Attendance ID)</th>
                    <th class="px-4 py-2 border border-gray-200">Shift Type</th>
                    <th class="px-4 py-2 border border-gray-200">Shift Date</th>
                </tr>
            </thead>
            <tbody id="shiftTableBody">
                <tr>
                    <td class="px-4 py-2 border border-gray-200">1</td>
                    <td class="px-4 py-2 border border-gray-200">Juan Dela Cruz</td>
                    <td class="px-4 py-2 border border-gray-200">Morning</td>
                    <td class="px-4 py-2 border border-gray-200">2025-09-10</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 border border-gray-200">2</td>
                    <td class="px-4 py-2 border border-gray-200">Maria Santos</td>
                    <td class="px-4 py-2 border border-gray-200">Afternoon</td>
                    <td class="px-4 py-2 border border-gray-200">2025-09-10</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 border border-gray-200">3</td>
                    <td class="px-4 py-2 border border-gray-200">Pedro Reyes</td>
                    <td class="px-4 py-2 border border-gray-200">Night</td>
                    <td class="px-4 py-2 border border-gray-200">2025-09-10</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
';

Layout($children);
?>

<script>
// Elements
const employeeSelect = document.getElementById("employee");
const shiftTableBody = document.getElementById("shiftTableBody");
const msg = document.getElementById("msg");

// Load attendance
function loadAttendance() {
    fetch("http://localhost/hr3-microfinance/api/attendance.php")
        .then(res => res.json())
        .then(data => {
            employeeSelect.innerHTML = "<option value=''>Select Employee</option>";
            data.forEach(att => {
                const option = document.createElement("option");
                option.value = att.id; // attendance_id
                option.text = `${att.employee_name}`;
                employeeSelect.appendChild(option);
            });
        })
        .catch(err => console.error("Error loading attendance:", err));
}

// Load assigned shifts
function loadShifts() {
    fetch("http://localhost/hr3-microfinance/api/shifts.php")
        .then(res => res.json())
        .then(data => {
            shiftTableBody.innerHTML = "";
            if (!data || data.length === 0) {
                shiftTableBody.innerHTML = "<tr><td colspan='4' class='px-4 py-2 border text-center'>No shifts assigned.</td></tr>";
                return;
            }
            data.forEach((shift, index) => {
                shiftTableBody.innerHTML += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 border border-gray-200">${index + 1}</td>
                        <td class="px-4 py-2 border border-gray-200">${shift.employee_name}</td>
                        <td class="px-4 py-2 border border-gray-200">${shift.shift_type}</td>
                        <td class="px-4 py-2 border border-gray-200">${shift.shift_date}</td>
                    </tr>
                `;
            });
        })
        .catch(err => console.error("Error loading shifts:", err));
}


// Modal logic for Assign Shift
const assignShiftModal = document.getElementById("assignShiftModal");
const openShiftModalBtn = document.getElementById("openShiftModalBtn");
const closeShiftModalBtn = document.getElementById("closeShiftModalBtn");

openShiftModalBtn.addEventListener("click", function(e) {
    e.preventDefault();
    assignShiftModal.classList.remove("hidden");
});

closeShiftModalBtn.addEventListener("click", function() {
    assignShiftModal.classList.add("hidden");
    document.getElementById("assignShiftForm").reset();
    msg.innerText = "";
});

// Assign shift
document.getElementById("assignShiftForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const attendance_id = employeeSelect.value;
    const shift_type = document.getElementById("shift").value;
    const shift_date = document.getElementById("date").value;

    if (!attendance_id || !shift_type || !shift_date) {
        msg.innerText = "Please fill in all fields.";
        msg.classList.remove("text-green-600");
        msg.classList.add("text-red-600");
        return;
    }

    fetch("http://localhost/hr3-microfinance/api/shifts.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ attendance_id, shift_type, shift_date })
    })
    .then(res => res.json())
    .then(data => {
        if (data.message) {
            msg.innerText = data.message;
            msg.classList.remove("text-red-600");
            msg.classList.add("text-green-600");
            this.reset();
            loadShifts(); // refresh table
            setTimeout(() => {
                assignShiftModal.classList.add("hidden");
                msg.innerText = "";
            }, 1200);
        } else if (data.error) {
            msg.innerText = data.error;
            msg.classList.remove("text-green-600");
            msg.classList.add("text-red-600");
        }
    })
    .catch(err => {
        console.error(err);
        msg.innerText = "Error assigning shift.";
        msg.classList.remove("text-green-600");
        msg.classList.add("text-red-600");
    });
});

// Initial load
loadAttendance();
loadShifts();
</script>
