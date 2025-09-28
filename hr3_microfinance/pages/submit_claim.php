<?php
include '../layout/Layout.php';
date_default_timezone_set('Asia/Manila');

// Claim types for dropdown
$claimTypes = ["Travel", "Meal", "Accommodation", "Other"];

$children = '
<div class="p-6 bg-gray-50 min-h-screen">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2">
            <i class="bx bx-receipt text-purple-600 text-3xl"></i>
            <h1 class="text-3xl font-bold text-gray-800">Submit Claim</h1>
        </div>
        <span class="text-sm text-gray-500">Today: ' . date("F d, Y") . '</span>
    </div>

    <!-- Open modal button -->
    <div class="text-end mb-6">
        <button id="openFormBtn" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
            Add New Claim
        </button>
    </div>

    <!-- Claims Table -->
    <div class="overflow-x-auto bg-white rounded-xl shadow p-4 max-w-10xl mx-auto">
        <table class="min-w-full border border-gray-200">
            <thead>
                <tr class="bg-gray-100 text-center">
                    <th class="px-4 py-2 border">Employee</th>
                    <th class="px-4 py-2 border">Claim Type</th>
                    <th class="px-4 py-2 border">Amount</th>
                    <th class="px-4 py-2 border">Date</th>
                    <th class="px-4 py-2 border">Status</th>
                    <th class="px-4 py-2 border">Actions</th>
                </tr>
            </thead>
            <tbody id="claimsTable">
                <tr><td colspan="6" class="text-center px-4 py-2">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Claim Modal -->
<div id="claimFormModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl p-6 max-w-lg w-full">
        <h2 class="text-2xl font-bold mb-4" id="modalTitle">Submit Claim</h2>
        <form id="submitClaimForm">
            <input type="hidden" id="claimId">
            <div class="mb-3">
                <label class="block text-gray-700 font-semibold mb-1">Employee</label>
                <input type="text" id="employee" list="employeeList" class="w-full border rounded-lg p-2" placeholder="Enter employee name">
                <datalist id="employeeList"></datalist>
            </div>

            <div class="mb-3">
                <label class="block text-gray-700 font-semibold mb-1">Claim Type</label>
                <select id="claimType" class="w-full border rounded-lg p-2">
                    <option value="">Select Claim Type</option>';
foreach ($claimTypes as $type) {
    $children .= '<option value="' . $type . '">' . $type . '</option>';
}
$children .= '
                </select>
            </div>

            <div class="mb-3" id="otherReasonWrapper" style="display:none;">
                <label class="block text-gray-700 font-semibold mb-1">Other Reason</label>
                <input type="text" id="otherReason" class="w-full border rounded-lg p-2" placeholder="Specify other claim reason">
            </div>

            <div class="mb-3">
                <label class="block text-gray-700 font-semibold mb-1">Amount</label>
                <input type="number" id="amount" class="w-full border rounded-lg p-2" min="0" step="0.01">
            </div>

            <div class="mb-3">
                <label class="block text-gray-700 font-semibold mb-1">Date</label>
                <input type="date" id="claimDate" class="w-full border rounded-lg p-2" value="' . date("Y-m-d") . '">
            </div>

            <div class="mb-3">
                <label class="block text-gray-700 font-semibold mb-1">Status</label>
                <select id="claimStatus" class="w-full border rounded-lg p-2">
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" id="cancelBtn" class="px-4 py-2 rounded-lg border hover:bg-gray-100 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-purple-600 text-white hover:bg-purple-700 transition">Submit</button>
            </div>
        </form>
        <p id="msg" class="mt-3 font-semibold text-green-600"></p>
    </div>
</div>

';

Layout($children);
?>

<script>
// Open/close modal
const modal = document.getElementById("claimFormModal");
const modalTitle = document.getElementById("modalTitle");
document.getElementById("openFormBtn").addEventListener("click", () => {
    modalTitle.innerText = "Submit Claim";
    document.getElementById("claimId").value = "";
    document.getElementById("submitClaimForm").reset();
    // default status to Pending on new
    const st = document.getElementById('claimStatus'); if (st) st.value = 'Pending';
    modal.classList.remove("hidden");
});
document.getElementById("cancelBtn").addEventListener("click", () => modal.classList.add("hidden"));

// toggle other reason input when claim type changes
const claimTypeEl = document.getElementById('claimType');
if (claimTypeEl) {
    claimTypeEl.addEventListener('change', () => {
        const wrapper = document.getElementById('otherReasonWrapper');
        if (!wrapper) return;
        if (claimTypeEl.value === 'Other') wrapper.style.display = '';
        else wrapper.style.display = 'none';
    });
}

// Load claims from API
function loadClaims() {
    fetch("../api/claims.php")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("claimsTable");
        tbody.innerHTML = "";
        if(!data || data.length === 0){
            tbody.innerHTML = `<tr><td colspan="6" class="text-center px-4 py-2">No claims submitted.</td></tr>`;
            return;
        }
        data.forEach(cl => {
            tbody.innerHTML += `
                <tr class="text-center hover:bg-gray-50">
                    <td class="px-4 py-2 border">${cl.employee_name}</td>
                    <td class="px-4 py-2 border">${cl.claim_type || cl.description}</td>
                    <td class="px-4 py-2 border">₱${parseFloat(cl.amount || 0).toFixed(2)}</td>
                    <td class="px-4 py-2 border">${cl.claim_date || cl.created_at || ''}</td>
                    <td class="px-4 py-2 border">${cl.status || ''}</td>
                    <td class="px-4 py-2 border">
                        <button onclick="editClaim(${cl.id})" class="text-blue-600 hover:underline mr-2">Edit</button>
                        <button onclick="deleteClaim(${cl.id})" class="text-red-600 hover:underline">Delete</button>
                    </td>
                </tr>
            `;
        });
    });
}

// Populate employee datalist for suggestions
function populateEmployeeList(){
    fetch('../api/employees.php')
    .then(res => res.json())
    .then(data => {
        const dl = document.getElementById('employeeList');
        if(!dl) return;
        dl.innerHTML = '';
        if(!Array.isArray(data)) return;
        data.forEach(emp => {
            const opt = document.createElement('option');
            opt.value = emp.name;
            dl.appendChild(opt);
        });
    }).catch(err => console.error('populateEmployeeList error', err));
}

// Submit or update claim via API
document.getElementById("submitClaimForm").addEventListener("submit", function(e){
    e.preventDefault();
    const claimId = document.getElementById("claimId").value;
    const employee = document.getElementById("employee").value.trim();
    const type = document.getElementById("claimType").value;
    const otherReason = document.getElementById('otherReason') ? document.getElementById('otherReason').value.trim() : '';
    const amount = document.getElementById("amount").value;
    const date = document.getElementById("claimDate").value;
    const status = document.getElementById('claimStatus') ? document.getElementById('claimStatus').value : 'Pending';
    const msg = document.getElementById("msg");

    if(!employee || !type || !amount || !date){
        msg.innerText = "Please fill in all fields.";
        msg.classList.remove("text-green-600");
        msg.classList.add("text-red-600");
        return;
    }

    // If 'Other' selected, require otherReason
    let finalType = type;
    if (type === 'Other') {
        if (!otherReason) {
            msg.innerText = 'Please specify the other reason.';
            msg.classList.remove('text-green-600');
            msg.classList.add('text-red-600');
            return;
        }
        finalType = otherReason;
    }

    const method = claimId ? "PUT" : "POST";
    const url = "../api/claims.php" + (claimId ? "?id=" + claimId : "");

    fetch(url, {
        method: method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ employee_name: employee, claim_type: finalType, amount: amount, claim_date: date, status: status })
    })
    .then(res => res.json())
    .then(data => {
        if(data.message || data.id || data.ok){
            msg.innerText = data.message || 'Saved successfully.';
            msg.classList.remove("text-red-600");
            msg.classList.add("text-green-600");
            document.getElementById("submitClaimForm").reset();
            modal.classList.add("hidden");
            loadClaims();
        } else if(data.error){
            msg.innerText = data.error;
            msg.classList.remove("text-green-600");
            msg.classList.add("text-red-600");
        }
    }).catch(err => {
        msg.innerText = 'Network or server error.';
        msg.classList.remove("text-green-600");
        msg.classList.add("text-red-600");
        console.error('submit claim error', err);
    });
});

// Edit claim
function editClaim(id){
    fetch("../api/claims.php?id=" + id)
    .then(res => res.json())
    .then(cl => {
        modalTitle.innerText = "Edit Claim";
        document.getElementById("claimId").value = cl.id;
        document.getElementById("employee").value = cl.employee_name;
        const known = ['Travel','Meal','Accommodation','Other'];
        const ct = cl.claim_type || cl.description || '';
        if (known.includes(ct)) {
            document.getElementById('claimType').value = ct;
            const wrapper = document.getElementById('otherReasonWrapper'); if (wrapper) wrapper.style.display = (ct === 'Other' ? '' : 'none');
            if (ct === 'Other') document.getElementById('otherReason').value = '';
        } else {
            // custom reason => treat as Other
            document.getElementById('claimType').value = 'Other';
            const wrapper = document.getElementById('otherReasonWrapper'); if (wrapper) wrapper.style.display = '';
            document.getElementById('otherReason').value = ct;
        }
        document.getElementById("amount").value = cl.amount || '';
        document.getElementById("claimDate").value = cl.claim_date || cl.created_at || '';
        const st = document.getElementById('claimStatus'); if (st) st.value = cl.status || 'Pending';
        modal.classList.remove("hidden");
    });
}

// Delete claim
function deleteClaim(id){
    if(!confirm("Are you sure you want to delete this claim?")) return;
    fetch("../api/claims.php?id=" + id, {
        method: "DELETE"
    })
    .then(res => res.json())
    .then(data => {
        if(data.message || data.ok){
            alert(data.message || 'Deleted');
            loadClaims();
        } else if(data.error){
            alert(data.error);
        }
    });
}

// Initial load
populateEmployeeList();
loadClaims();
</script>