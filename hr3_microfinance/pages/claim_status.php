<?php
include '../layout/Layout.php';

date_default_timezone_set('Asia/Manila');

$statuses = ["All", "Pending", "Approved", "Rejected"];

 $children = '
<div class="p-6 bg-gray-50 min-h-screen">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <i class="bx bx-task text-blue-600 text-3xl"></i>
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Claim Status</h1>
                <p class="text-sm text-gray-500">Overview of all submitted claims</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-sm text-gray-500">Today: ' . date("F d, Y") . '</div>
        </div>
    </div>

    <!-- Controls: Search, Count, Filter -->
    <div class="mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <input id="searchBox" type="search" placeholder="Search employee, type or amount..." class="border p-2 rounded-lg w-full sm:w-80" />
        </div>

        <div class="flex items-center gap-3 ml-auto">
            <div class="text-sm text-gray-700">Total: <span id="claimCount" class="font-semibold">-</span></div>
            <label for="statusFilter" class="font-semibold text-gray-700">Status:</label>
            <select id="statusFilter" class="border rounded-lg p-2 pl-3">';
            foreach ($statuses as $status) {
                $children .= '<option value="' . $status . '">' . $status . '</option>';
            }
            $children .= '
            </select>
        </div>
    </div>

    <!-- Claims Table -->
    <div class="overflow-x-auto bg-white rounded-xl shadow p-4 max-w-10xl mx-auto">
        <table id="claimsTable" class="min-w-full border border-gray-200 divide-y divide-gray-100">
            <thead>
                <tr class="bg-gray-50 text-left text-sm text-gray-600">
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Claim Type</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody id="claimsBody" class="bg-white">
                <tr><td colspan="5" class="text-center px-4 py-4 text-gray-500">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

';

Layout($children);
?>

<script>
// Helpers
function formatCurrencyPHP(value) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value);
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

let CLAIMS_CACHE = [];

// Render claims into table based on current filters
function renderClaims(filterStatus = 'All', searchTerm = ''){
    const tbody = document.getElementById("claimsBody");
    tbody.innerHTML = "";

    const filtered = CLAIMS_CACHE.filter(cl => {
        const statusMatch = (filterStatus === 'All') || (cl.status === filterStatus);
        if(!statusMatch) return false;
        if(!searchTerm) return true;
        const term = searchTerm.toLowerCase();
        return (cl.employee_name || '').toLowerCase().includes(term)
            || (cl.claim_type || cl.description || '').toLowerCase().includes(term)
            || (String(cl.amount || '')).toLowerCase().includes(term);
    });

    document.getElementById('claimCount').textContent = filtered.length;

    if(filtered.length === 0){
        tbody.innerHTML = `<tr><td colspan="5" class="text-center px-4 py-4 text-gray-500">No claims match your criteria.</td></tr>`;
        return;
    }

    filtered.forEach(cl => {
        const status = cl.status || '';
        let badgeClass = 'text-gray-700 bg-gray-100';
        if(status === 'Approved') badgeClass = 'text-green-700 bg-green-100';
        if(status === 'Pending') badgeClass = 'text-yellow-700 bg-yellow-100';
        if(status === 'Rejected') badgeClass = 'text-red-700 bg-red-100';

        const claimType = cl.claim_type || cl.description || '';
        const amount = Number(cl.amount) || 0;
        const date = cl.claim_date || cl.created_at || '';

        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50';
        tr.setAttribute('data-status', status);

        tr.innerHTML = `
            <td class="px-4 py-3 border-b" style="min-width:160px">${cl.employee_name || ''}</td>
            <td class="px-4 py-3 border-b">${claimType}</td>
            <td class="px-4 py-3 border-b">${formatCurrencyPHP(amount)}</td>
            <td class="px-4 py-3 border-b">${formatDate(date)}</td>
            <td class="px-4 py-3 border-b"><span class="px-3 py-1 rounded-full text-sm font-semibold ${badgeClass}">${status}</span></td>
        `;

        tbody.appendChild(tr);
    });
}

// Fetch all claims from API and cache them
function loadClaims() {
    fetch('../api/claims.php')
    .then(res => {
        if (!res.ok) throw new Error('Network response not ok: ' + res.status);
        const ct = res.headers.get('content-type') || '';
        if (!ct.includes('application/json')) throw new Error('Expected JSON response but got: ' + ct);
        return res.json();
    })
    .then(data => {
        CLAIMS_CACHE = Array.isArray(data) ? data : [];
        // Initialize controls
        const status = document.getElementById('statusFilter').value;
        const search = document.getElementById('searchBox').value.trim();
        renderClaims(status, search);
    })
    .catch(err => {
        console.error('loadClaims error', err);
        const tbody = document.getElementById("claimsBody");
        if (tbody) tbody.innerHTML = `<tr><td colspan="5" class="text-center px-4 py-4 text-red-600">Error loading claims: ${err.message}</td></tr>`;
        document.getElementById('claimCount').textContent = '0';
    });
}

// Controls: filter and search
document.getElementById('statusFilter').addEventListener('change', function(){
    const status = this.value;
    const search = document.getElementById('searchBox').value.trim();
    renderClaims(status, search);
});

document.getElementById('searchBox').addEventListener('input', function(){
    const search = this.value.trim();
    const status = document.getElementById('statusFilter').value;
    renderClaims(status, search);
});

// Initial load
loadClaims();
</script>