<?php
include '../layout/Layout.php';

$children = '
<div class="h-[130%] overflow-y-auto bg-gray-50 p-6">
    <!-- Dashboard Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-2">
            <i class="bx bx-grid-alt text-indigo-600 text-3xl"></i>
            <h1 class="text-3xl font-bold text-gray-800">HR III Dashboard</h1>
        </div>
        <span class="text-sm text-gray-500">Updated: ' . date("F d, Y") . '</span>
    </div>

    <!-- HR Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
            <i class="bx bx-time-five text-indigo-600 text-3xl mb-3"></i>
            <h3 id="attendanceCount" class="text-xl font-semibold text-gray-800">-</h3>
            <p class="text-gray-500 text-sm">Attendance Records</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
            <i class="bx bx-calendar text-green-600 text-3xl mb-3"></i>
            <h3 id="shiftsCount" class="text-xl font-semibold text-gray-800">-</h3>
            <p class="text-gray-500 text-sm">Scheduled Shifts</p>
        </div>
       <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
            <i class="bx bx-time text-yellow-500 text-3xl mb-3"></i>
            <h3 id="timesheetCount" class="text-xl font-semibold text-gray-800">-</h3>
            <p class="text-gray-500 text-sm">Timesheets Pending</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
            <i class="bx bx-calendar-alt text-red-600 text-3xl mb-3"></i>
            <h3 id="leavesCount" class="text-xl font-semibold text-gray-800">-</h3>
            <p class="text-gray-500 text-sm">Leave Requests</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
            <i class="bx bx-receipt text-purple-600 text-3xl mb-3"></i>
            <h3 id="claimsCount" class="text-xl font-semibold text-gray-800">-</h3>
            <p class="text-gray-500 text-sm">Claims Submitted</p>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-xl shadow h-[400px] flex flex-col">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Attendance This Month</h2>
            <div class="flex-1 overflow-y-auto">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow h-[400px] flex flex-col">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Leaves by Type</h2>
            <div class="flex-1 overflow-y-auto">
                <canvas id="leaveChart"></canvas>
            </div>
        </div>
    </div>
</div>


';

Layout($children);
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Create Chart.js instances with empty datasets — we'll populate them from the API
    const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
    const attendanceChart = new Chart(attendanceCtx, {
        type: 'line',
        data: { labels: ['Week 1','Week 2','Week 3','Week 4'], datasets: [{ label: 'Attendance', data: [0,0,0,0], borderColor:'#6366F1', backgroundColor:'rgba(99,102,241,0.2)', fill:true, tension:0.4 }] },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'top' } }, scales:{ y:{ beginAtZero:true } } }
    });

    const leaveCtx = document.getElementById('leaveChart').getContext('2d');
    const leaveChart = new Chart(leaveCtx, {
        type: 'doughnut',
        data: { labels: [], datasets: [{ label: 'Leaves', data: [], backgroundColor: [] }] },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
    });

    // Helper to generate pastel colors
    function pastelColor(i) { const hues = [140, 45, 0, 270, 200, 30, 330]; const h = hues[i % hues.length]; return `hsl(${h} 70% 75%)`; }

    function loadChartData(){
        // Attendance: fetch all attendance for current month and bucket by week
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth()+1).padStart(2,'0');
        const monthStart = `${yyyy}-${mm}-01`;
        fetch('../api/attendance.php?date=' + monthStart, { cache:'no-cache' })
        .then(r => r.json())
        .then(rows => {
            // rows may be all attendance for a single date when ?date used; if API doesn't support month filter, fetch all and filter here
            let items = Array.isArray(rows) ? rows : [];
            // If API returned rows only for that single date, fall back to fetching all and filtering client-side
            if (items.length === 0) {
                return fetch('../api/attendance.php', { cache:'no-cache' }).then(r=>r.json());
            }
            return items;
        })
        .then(all => {
            // Filter items for current month
            const items = (Array.isArray(all) ? all : []).filter(it => it.date && it.date.startsWith(`${yyyy}-${mm}`));
            // Bucket into weeks (Week 1: days 1-7, 2:8-14, 3:15-21, 4:22-end)
            const buckets = [0,0,0,0];
            items.forEach(it => {
                const d = parseInt(it.date.split('-')[2]||0,10);
                if (d >=1 && d <=7) buckets[0]++;
                else if (d <=14) buckets[1]++;
                else if (d <=21) buckets[2]++;
                else buckets[3]++;
            });
            attendanceChart.data.labels = ['Week 1','Week 2','Week 3','Week 4'];
            attendanceChart.data.datasets[0].data = buckets;
            attendanceChart.update();
        }).catch(err=>{ console.warn('attendance chart load err', err); });

        // Leaves: fetch all leaves and count by reason/leave_type
        fetch('../api/leaves.php', { cache:'no-cache' }).then(r=>r.json()).then(list=>{
            const byType = {};
            (Array.isArray(list)?list:[]).forEach(l => { const k = (l.leave_type || l.reason || 'Other').toString(); byType[k] = (byType[k]||0)+1; });
            const labels = Object.keys(byType);
            const data = labels.map(l=>byType[l]);
            leaveChart.data.labels = labels;
            leaveChart.data.datasets[0].data = data;
            leaveChart.data.datasets[0].backgroundColor = labels.map((_,i)=>pastelColor(i));
            leaveChart.update();
        }).catch(err=>{ console.warn('leave chart load err', err); });
    }

    // initial load and periodic refresh
    loadChartData();
    setInterval(loadChartData, 60*1000);

    
function loadTimesheetCount() {
    fetch('http://localhost/hr3-microfinancial/api/timesheets.php')
    .then(res => res.json())
    .then(data => {
        // Check if API returns count
        const count = data.count !== undefined ? data.count : 0;
        document.getElementById('timesheetCount').innerText = count;
    })
    .catch(err => {
        console.error('Failed to fetch timesheet count:', err);
        document.getElementById('timesheetCount').innerText = '0';
    });
}

// Initial load
loadTimesheetCount();
function loadDashboardCounts() {
    // Attendance count (total records)
    fetch('../api/attendance.php', { cache: 'no-cache' })
    .then(r => r.json())
    .then(data => {
        if (Array.isArray(data)) document.getElementById('attendanceCount').innerText = String(data.length);
        else document.getElementById('attendanceCount').innerText = '-';
    }).catch(() => { document.getElementById('attendanceCount').innerText = '-'; });

    // Shifts count
    fetch('../api/shifts.php', { cache: 'no-cache' })
    .then(r => r.json())
    .then(data => { document.getElementById('shiftsCount').innerText = String(Array.isArray(data) ? data.length : '-'); })
    .catch(() => { document.getElementById('shiftsCount').innerText = '-'; });

    // Timesheets pending (reuse existing loader logic)
    loadTimesheetCount();

    // Leaves pending
    fetch('../api/leaves.php', { cache: 'no-cache' })
    .then(r => r.json())
    .then(data => {
        let count = 0;
        if (Array.isArray(data)) count = data.reduce((acc, it) => { const s = (it.status || '').toString().toLowerCase(); if (!s || s === 'pending') return acc + 1; return acc; }, 0);
        document.getElementById('leavesCount').innerText = String(count);
    }).catch(() => { document.getElementById('leavesCount').innerText = '-'; });

    // Claims submitted (total)
    fetch('../api/claims.php', { cache: 'no-cache' })
    .then(r => r.json())
    .then(data => { document.getElementById('claimsCount').innerText = String(Array.isArray(data) ? data.length : '-'); })
    .catch(() => { document.getElementById('claimsCount').innerText = '-'; });
}

// Load dashboard counts now and refresh every minute
loadDashboardCounts();
setInterval(loadDashboardCounts, 60 * 1000);
</script>
