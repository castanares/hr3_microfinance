<?php
include '../layout/Layout.php';

date_default_timezone_set('Asia/Manila');

// Sample user data (replace with DB query / session)
$user = [
    "name" => "Juan Dela Cruz",
    "email" => "juan.delacruz@example.com",
    "role" => "Employee",
    "phone" => "09123456789",
    "department" => "HR"
];

// Sample recent activities (replace with DB query)
$activities = [
    ["activity" => "Clocked in", "date" => "2025-09-01 08:00 AM"],
    ["activity" => "Applied Leave", "date" => "2025-09-01 09:15 AM"],
    ["activity" => "Submitted Claim", "date" => "2025-09-02 10:30 AM"]
];

$children = '
<div class="p-6 bg-gray-50 min-h-screen">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2">
            <i class="bx bx-user text-indigo-600 text-3xl"></i>
            <h1 class="text-3xl font-bold text-gray-800">Profile</h1>
        </div>
        <span class="text-sm text-gray-500">Today: ' . date("F d, Y") . '</span>
    </div>

    <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Profile Card -->
        <div class="bg-white rounded-xl shadow p-6 flex flex-col items-center">
            <div class="w-24 h-24 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-3xl font-bold mb-4">
                ' . strtoupper($user["name"][0]) . '
            </div>
            <h2 class="text-xl font-bold text-gray-800 mb-1">' . $user["name"] . '</h2>
            <p class="text-gray-500 mb-2">' . $user["role"] . '</p>
            <p class="text-gray-500 mb-2">' . $user["email"] . '</p>
            <p class="text-gray-500 mb-2">' . $user["phone"] . '</p>
            <p class="text-gray-500 mb-4">' . $user["department"] . '</p>
            <button id="editProfileBtn" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                Edit Profile
            </button>
        </div>

        <!-- Recent Activities -->
        <div class="md:col-span-2 bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Activity</h3>
            <ul class="divide-y divide-gray-200">
';

foreach ($activities as $act) {
    $children .= '
                <li class="py-2 flex justify-between">
                    <span>' . $act["activity"] . '</span>
                    <span class="text-gray-400 text-sm">' . $act["date"] . '</span>
                </li>
    ';
}

$children .= '
            </ul>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="profileModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl p-6 max-w-md w-full">
        <h2 class="text-xl font-bold mb-4">Edit Profile</h2>
        <form id="editProfileForm">
            <div class="mb-3">
                <label class="block text-gray-700 font-semibold mb-1">Name</label>
                <input type="text" id="editName" class="w-full border rounded-lg p-2" value="' . $user["name"] . '">
            </div>
            <div class="mb-3">
                <label class="block text-gray-700 font-semibold mb-1">Email</label>
                <input type="email" id="editEmail" class="w-full border rounded-lg p-2" value="' . $user["email"] . '">
            </div>
            <div class="mb-3">
                <label class="block text-gray-700 font-semibold mb-1">Phone</label>
                <input type="text" id="editPhone" class="w-full border rounded-lg p-2" value="' . $user["phone"] . '">
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" id="cancelProfileBtn" class="px-4 py-2 rounded-lg border hover:bg-gray-100 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
// Open modal
document.getElementById("editProfileBtn").addEventListener("click", () => {
    document.getElementById("profileModal").classList.remove("hidden");
});

// Cancel modal
document.getElementById("cancelProfileBtn").addEventListener("click", () => {
    document.getElementById("profileModal").classList.add("hidden");
});

// Handle profile form submission
document.getElementById("editProfileForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const name = document.getElementById("editName").value;
    const email = document.getElementById("editEmail").value;
    const phone = document.getElementById("editPhone").value;

    // TODO: Save data to backend
    alert("Profile updated: " + name + ", " + email + ", " + phone);
    document.getElementById("profileModal").classList.add("hidden");
});
</script>
';

Layout($children);
?>
