<?php
function Layout($children) {
    $currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HR Dashboard Sidebar</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

body {
  font-family: 'Inter', sans-serif;
  /* Do not let body scroll; we'll make only the main content area scrollable */
  height: 100vh;
  overflow: hidden;
}

/* Sidebar */
.sidebar {
    transition: transform 0.3s ease-in-out;
    transform: translateX(0);
    background-color: #30885c; /* light green */
}
.sidebar.hidden {
    transform: translateX(-100%);
}
.sidebar-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    color: rgba(0, 0, 0, 0.8);
}
.sidebar-item:hover {
    background-color: rgba(0, 0, 0, 0.05);
}
.sidebar-item.active {
    background-color: #A7F3D0; /* light green */
    color: #065F46;             /* dark text */
}
.sidebar-item .bx {
    color: rgba(0,0,0,0.7);
    font-size: 1.5rem;
    margin-right: 0.75rem;
}
.sidebar-item.active .bx {
    color: #065F46;
}
.sidebar h3 {
    color: #065F46;
}

/* Header */
header {
    background-color: #065F46; /* dark green */
}
/* Hide scrollbar but keep scrollable */
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }

/* Dropdowns */
.profile-dropdown,
.notif-dropdown {
    display: none;
    position: absolute;
    right: 0;
    margin-top: 0.5rem;
    background-color: #065F46;
    color: white;
    top: 30px;
    border-radius: 0.375rem;
    width: 160px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    z-index: 50;
}
.profile-dropdown a,
.notif-dropdown a {
    display: block;
    padding: 0.5rem 1rem;
    color: white;
    text-decoration: none;
    transition: background-color 0.2s;
}
.profile-dropdown a:hover,
.notif-dropdown a:hover {
    background-color: #047857;
}
.profile-button span,
.notif-button span {
    color: white;
}
</style>
</head>
<body class="flex flex-col min-h-screen">

<!-- Header -->
<header class="shadow p-4 flex justify-between items-center w-full z-10 relative fixed top-0 left-0">
    <div class="flex items-center space-x-4">
        <i id="menu-icon" class='bx bx-menu text-3xl text-white cursor-pointer'></i>
        <div class="text-2xl font-bold text-white">
            <i class='bx bx-briefcase-alt-2 mr-2'></i>Microfinance EIS
        </div>
    </div>

    <div class="flex items-center space-x-4 relative">
        <!-- Notification -->
        <div id="notif-button" class="notif-button relative cursor-pointer">
            <i class='bx bx-bell text-2xl text-white'></i>
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">1</span>
            <div id="notif-dropdown" class="notif-dropdown">
                <a href="#">New leave request</a>
                <a href="#">Payroll updated</a>
                <a href="#">System alert</a>
            </div>
        </div>

        <!-- Profile -->
        <div id="profile-button" class="profile-button flex items-center space-x-2 cursor-pointer relative">
            <img src="../images/img6.jpg" alt="User Avatar" class="rounded-full h-10 w-10">
            <span class="font-semibold">Yor Jan(admin)</span>
            <i class='bx bx-chevron-down text-white'></i>
            <div id="profile-dropdown" class="profile-dropdown">
                <a href="profile.php">Profile</a>
                <a href="http://localhost/hr3-microfinancial/">Logout</a>
            </div>
        </div>
    </div>
</header>

<!-- Main Container -->
<div class="flex flex-1 overflow-hidden" style="margin-top:10px; height: calc(100vh - 64px);">
  <aside id="sidebar" class="sidebar w-64 p-4 flex flex-col justify-between h-full mt-0 overflow-y-auto scrollbar-hide fixed top-16 left-0">
    <div>
        <nav>
            <h3 class="text-xs uppercase font-semibold mb-2">Human Resource III</h3>
    <ul>
    <!-- Dashboard -->
    <li class="mb-1">
      <a href="dashboard.php" class="sidebar-item">
        <i class='bx bx-home'></i> Dashboard
      </a>
    </li>

    <!-- Time & Attendance -->
    <li class="mb-1">
      <button class="sidebar-item flex justify-between w-full" onclick="toggleDropdown('attendance-dropdown')">
        <span><i class='bx bx-time-five'></i> Time Track</span>
        <i class='bx bx-chevron-down'></i>
      </button>
      <ul id="attendance-dropdown" class="ml-6 mt-1 hidden">
        <li><a href="attendance_log.php" class="sidebar-item text-sm">Attendance Log</a></li>
        <!-- <li><a href="clock_in.php" class="sidebar-item text-sm">Clock In/Out</a></li> -->
      </ul>
    </li>

    <!-- Shift & Schedule -->
    <li class="mb-1">
      <button class="sidebar-item flex justify-between w-full" onclick="toggleDropdown('shift-dropdown')">
        <span><i class='bx bx-calendar'></i> Shift Plan</span>
        <i class='bx bx-chevron-down'></i>
      </button>
      <ul id="shift-dropdown" class="ml-6 mt-1 hidden">
        <li><a href="view_shifts.php" class="sidebar-item text-sm">View Shifts</a></li>
        <!-- <li><a href="assign_duty.php" class="sidebar-item text-sm">Assign Duty</a></li> -->
      </ul>
    </li>

    <!-- Timesheet Management -->
    <li class="mb-1">
      <button class="sidebar-item flex justify-between w-full" onclick="toggleDropdown('timesheet-dropdown')">
        <span><i class='bx bx-time'></i> Timesheet Log</span>
        <i class='bx bx-chevron-down'></i>
      </button>
      <ul id="timesheet-dropdown" class="ml-6 mt-1 hidden">
        <li><a href="view_sheet.php" class="sidebar-item text-sm">View Sheet</a></li>
        <li><a href="add_sheet.php" class="sidebar-item text-sm">Employee log</a></li>
      </ul>
    </li>

    <!-- Leave Management -->
    <li class="mb-1">
      <button class="sidebar-item flex justify-between w-full" onclick="toggleDropdown('leave-dropdown')">
        <span><i class='bx bx-calendar-alt'></i> Leave Track</span>
        <i class='bx bx-chevron-down'></i>
      </button>
      <ul id="leave-dropdown" class="ml-6 mt-1 hidden">
        <li><a href="apply_leave.php" class="sidebar-item text-sm">Apply Leave</a></li>
        <li><a href="leave_history.php" class="sidebar-item text-sm">Leave History</a></li>
      </ul>
    </li>

    <!-- Claims & Reimbursement -->
    <li class="mb-1">
      <button class="sidebar-item flex justify-between w-full" onclick="toggleDropdown('claims-dropdown')">
        <span><i class='bx bx-receipt'></i> Claims</span>
        <i class='bx bx-chevron-down'></i>
      </button>
      <ul id="claims-dropdown" class="ml-6 mt-1 hidden">
        <li><a href="submit_claim.php" class="sidebar-item text-sm">Submit Claim</a></li>
        <li><a href="claim_status.php" class="sidebar-item text-sm">Claim Status</a></li>
      </ul>
    </li>

    <!-- Employee -->
    <li class="mb-1">
      <button class="sidebar-item flex justify-between w-full" onclick="toggleDropdown('employee-dropdown')">
        <span><i class='bx bx-user'></i> Employee</span>
        <i class='bx bx-chevron-down'></i>
      </button>
      <ul id="employee-dropdown" class="ml-6 mt-1 hidden">
        <li><a href="employee.php" class="sidebar-item text-sm">Employee List</a></li>

      </ul>
    </li>
</ul>




            
        </nav>
    </div>

   
</aside>

  <!-- Main Content -->
  <main id="main-content" class="flex-1 p-6 overflow-y-auto ml-64" style="height: calc(100vh - 64px);">
    <?php echo $children; ?>
  </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebarItems = document.querySelectorAll('.sidebar-item');
    const menuIcon = document.getElementById('menu-icon');
    const sidebar = document.getElementById('sidebar');

    const profileButton = document.getElementById('profile-button');
    const profileDropdown = document.getElementById('profile-dropdown');

    const notifButton = document.getElementById('notif-button');
    const notifDropdown = document.getElementById('notif-dropdown');

    // Sidebar toggle
    const isLargeScreen = window.innerWidth >= 1024;
    if (!isLargeScreen) sidebar.classList.add('hidden');
    menuIcon.addEventListener('click', () => sidebar.classList.toggle('hidden'));

   

    // Profile dropdown toggle
    profileButton.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdown.style.display = profileDropdown.style.display === 'block' ? 'none' : 'block';
        notifDropdown.style.display = 'none';
    });

    // Notification dropdown toggle
    notifButton.addEventListener('click', (e) => {
        e.stopPropagation();
        notifDropdown.style.display = notifDropdown.style.display === 'block' ? 'none' : 'block';
        profileDropdown.style.display = 'none';
    });

    // Close dropdowns on click outside
    window.addEventListener('click', () => {
        profileDropdown.style.display = 'none';
        notifDropdown.style.display = 'none';
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        const isLarge = window.innerWidth >= 1024;
        if (isLarge) sidebar.classList.remove('hidden');
        else sidebar.classList.add('hidden');
    });
});
function toggleDropdown(id) {
  const el = document.getElementById(id);
  el.classList.toggle('hidden');
}
</script>

</body>
</html>
<?php
}
?>