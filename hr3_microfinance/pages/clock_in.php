<?php
include '../layout/Layout.php';
date_default_timezone_set('Asia/Manila');

$children = '
<div class="p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-2">
            <i class="bx bx-time-five text-indigo-600 text-3xl"></i>
            <h1 class="text-3xl font-bold text-gray-800">Clock In</h1>
        </div>
        <span class="text-sm text-gray-500">Today: ' . date("F d, Y") . '</span>
    </div>

    <!-- Employee Info Card -->
    <div class="bg-white p-6 rounded-xl shadow max-w-md mx-auto mb-6">
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Employee Information</h2>
        <p class="text-gray-600">
            <strong>ID:</strong> 
            <a id="empIdLink" href="#" target="_blank" class="text-indigo-600 underline hover:text-indigo-800">
                <span id="empId">--</span>
            </a>
        </p>
        <p class="text-gray-600"><strong>Last Clock-In:</strong> <span id="lastClockIn">--:--:--</span></p>
    </div>

    <!-- Clock In Controls -->
    <div class="bg-white p-6 rounded-xl shadow max-w-md mx-auto text-center">
        <h2 id="currentTime" class="text-5xl font-bold text-indigo-600 mb-6"></h2>
        <button id="openQrBtn" class="bg-indigo-600 text-white px-8 py-3 rounded-lg hover:bg-indigo-700 transition">
            Scan QR to Clock In
        </button>
        <p id="clockInMsg" class="mt-4 text-green-600 font-semibold"></p>
    </div>
</div>

<!-- QR Scanner Modal -->
<div id="qrModal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50">
    <div class="bg-white p-6 rounded-xl shadow-lg relative max-w-lg w-full">
        <button id="closeQrBtn" class="absolute top-2 right-2 text-gray-500 hover:text-red-600">✖</button>
        <h2 class="text-xl font-semibold mb-4">Scan Employee QR</h2>
        <video id="qrVideo" class="w-full rounded-lg border"></video>
        <p id="qrStatus" class="text-gray-600 mt-2 text-center">Align QR code inside the box</p>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
// ========== Local "Employee Database" (Only for mapping IDs if needed) ==========
const employees = {
  "EMP-1024": { name: "Juan Dela Cruz" },
  "EMP-2048": { name: "Maria Santos" },
  "EMP-4096": { name: "Pedro Reyes" }
};

// ========== Current Time ==========
const currentTimeEl = document.getElementById("currentTime");
setInterval(() => {
  const now = new Date();
  currentTimeEl.textContent = now.toLocaleTimeString("en-PH", {hour12:false});
}, 1000);

// Elements
const qrModal = document.getElementById("qrModal");
const qrVideo = document.getElementById("qrVideo");
const qrStatus = document.getElementById("qrStatus");
const openQrBtn = document.getElementById("openQrBtn");
const closeQrBtn = document.getElementById("closeQrBtn");
const empIdEl = document.getElementById("empId");
const empIdLink = document.getElementById("empIdLink");
const lastClockInEl = document.getElementById("lastClockIn");

let scanning = false;
let videoStream = null;

// Open modal
openQrBtn.addEventListener("click", () => {
  qrModal.classList.remove("hidden");
  startQrScanner();
});

// Close modal
closeQrBtn.addEventListener("click", closeQrModal);
function closeQrModal() {
  qrModal.classList.add("hidden");
  if (videoStream) {
    videoStream.getTracks().forEach(track => track.stop());
  }
  scanning = false;
}

// Start QR scanner
function startQrScanner() {
  navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
    .then(stream => {
      qrVideo.srcObject = stream;
      videoStream = stream;
      qrVideo.setAttribute("playsinline", true);
      qrVideo.play();
      scanning = true;
      requestAnimationFrame(tick);
    })
    .catch(err => {
      qrStatus.textContent = "Camera access denied.";
    });
}

// Scan loop
function tick() {
  if (!scanning) return;
  if (qrVideo.readyState === qrVideo.HAVE_ENOUGH_DATA) {
    const canvas = document.createElement("canvas");
    canvas.width = qrVideo.videoWidth;
    canvas.height = qrVideo.videoHeight;
    const ctx = canvas.getContext("2d");
    ctx.drawImage(qrVideo, 0, 0, canvas.width, canvas.height);
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: "dontInvert" });

    if (code && code.data) {
      scanning = false;
      qrStatus.textContent = "QR detected!";
      handleQrData(code.data);
      return;
    }
  }
  requestAnimationFrame(tick);
}

// Handle QR payload
function handleQrData(data) {
  let employeeId = null;

  try {
    const parsed = JSON.parse(data);
    employeeId = parsed.employeeId || parsed.id;
  } catch {
    employeeId = data; // plain ID
  }

  if (!employeeId) {
    qrStatus.textContent = "Invalid QR.";
    scanning = true;
    requestAnimationFrame(tick);
    return;
  }

  // Update UI
  empIdEl.textContent = employeeId;
  empIdLink.href = "" + employeeId; // Example: navigate to Google with ID

  // Update last clock-in
  const now = new Date();
  const formatted = now.toLocaleTimeString("en-PH", {hour12:false});
  lastClockInEl.textContent = formatted;
  document.getElementById("clockInMsg").textContent = "Clocked in at " + formatted;

  closeQrModal();
}
</script>
';

Layout($children);
?>  