<?php
session_start();

// Dummy credentials for demonstration (replace with DB check)
$users = [
    ["email" => "admin@gmail.com", "password" => "admin123", "name" => "Admin"],
    ["email" => "user@example.com", "password" => "user123", "name" => "User"]
];

$msg = "";
$msgType = ""; // 'success' or 'error'

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";

    // Simple authentication
    foreach ($users as $user) {
        if ($user["email"] === $email && $user["password"] === $password) {
            $_SESSION["email"] = $user["email"];
            $_SESSION["name"] = $user["name"];
            $msg = "Login successful! Redirecting...";
            $msgType = "success";
            break;
        }   
    }

    if (!$msg) {
        $msg = "Invalid email or password!";
        $msgType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Microfinance EIS</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<!-- Toastify JS & CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>
<body class="bg-[#065F46] flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md">
    <h1 class="text-2xl font-bold text-center text-indigo-600 mb-6">
        <i class='bx bx-briefcase-alt-2 mr-2'></i> Microfinance EIS
    </h1>

    <form method="POST" class="space-y-4 relative">
        <div>
            <label for="email" class="block text-gray-700 font-semibold mb-1">Email</label>
            <input type="email" name="email" id="email" required
                   class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="relative">
            <label for="password" class="block text-gray-700 font-semibold mb-1">Password</label>
            <input type="password" name="password" id="password" required
                   class="w-full border rounded-lg p-2 pr-10 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <i id="togglePassword" class='bx bx-show absolute right-3 top-12 transform -translate-y-1/2 text-gray-500 cursor-pointer'></i>
        </div>

        <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition">
            Login
        </button>
    </form>

    <p class="mt-4 text-sm text-gray-100 text-center">
        &copy; 2025 Microfinance EIS
    </p>
</div>

<script>
// Toggle password visibility
const passwordInput = document.getElementById('password');
const togglePassword = document.getElementById('togglePassword');

togglePassword.addEventListener('click', () => {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    togglePassword.classList.toggle('bx-show');
    togglePassword.classList.toggle('bx-hide');
});

// Toast helper
function showToast(message, type) {
  Toastify({
    text: message,
    style: {
      background: type === 'success' ?
        "linear-gradient(to right, #00b09b, #96c93d)" :
        "linear-gradient(to right, #ff5f6d, #ffc371)"
    },
    duration: 3000,
    close: true
  }).showToast();
}

// Show toast if PHP $msg exists
<?php if($msg): ?>
    showToast("<?= $msg ?>", "<?= $msgType ?>");

    <?php if($msgType === "success"): ?>
        // Redirect after 1.5 seconds
        setTimeout(() => {
            window.location.href = "pages/dashboard.php";
        }, 1500);
    <?php endif; ?>
<?php endif; ?>
</script>

</body>
</html>
