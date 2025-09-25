<?php
// scripts/mark_absent.php
// Idempotent script to mark absent for shifts whose end_time has passed
// Intended to be run by Windows Task Scheduler at 12:00 and 18:00 daily

require_once __DIR__ . '/../api/db.php';

// Force the global/app timezone so CLI runs match the web app environment
date_default_timezone_set('Asia/Manila');

$now = new DateTime('now');
$today = $now->format('Y-m-d');

// Print timezone and current time for debugging when run from CLI/scheduler
echo "Timezone: " . date_default_timezone_get() . "\n";
echo "Now: " . $now->format('Y-m-d H:i:s') . "\n";

function parse_shift_time($date, $timeStr) {
    if (!$timeStr) return null;
    if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{1,2}:\d{2}/', $timeStr)) {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $timeStr) ?: DateTime::createFromFormat('Y-m-d H:i', $timeStr);
        if ($d) return $d;
    }
    $d = DateTime::createFromFormat('Y-m-d g:i A', $date . ' ' . $timeStr);
    if ($d) return $d;
    $d = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $timeStr);
    if ($d) return $d;
    $ts = strtotime($date . ' ' . $timeStr);
    if ($ts === false) return null;
    return (new DateTime())->setTimestamp($ts);
}

try {
    // Fetch shifts only for today. This script will be safe to run hourly and
    // will only mark absent for the current scheduled shifts (today).
    $stmt = $conn->prepare('SELECT * FROM shifts WHERE date = ?');
    $stmt->execute([$today]);
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $inserted = 0;
    foreach ($shifts as $shift) {
        $shiftDate = $shift['date'];
        $endTime = $shift['end_time'] ?? $shift['time_end'] ?? $shift['end'] ?? null;
        $endDt = parse_shift_time($shiftDate, $endTime);
        if (!$endDt) continue;
        // Only mark absent if the shift ended in the past
        if ($now <= $endDt) continue;

        $empId = $shift['employee_id'] ?? $shift['emp_id'] ?? null;
        if (!$empId) continue;

        // If any attendance exists for this employee/date (in or out or already absent), skip
        $check = $conn->prepare("SELECT COUNT(*) as c FROM attendance WHERE employee_id = ? AND date = ? AND (time_in IS NOT NULL OR time_out IS NOT NULL OR status_clock_out = 'Absent' OR Clock_In_Status = 'Absent')");
        $check->execute([$empId, $shiftDate]);
        $c = $check->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
        if ($c > 0) continue;

        // Insert absent row
        $shiftName = $shift['shift_name'] ?? $shift['shift'] ?? null;
        $sql = "INSERT INTO attendance (employee_id, shift, date, rfid, time_in, Clock_In_Status, time_out, status_clock_out)
                SELECT ?, ?, ?, NULL, NULL, ?, NULL, ?
                FROM DUAL
                WHERE NOT EXISTS (
                    SELECT 1 FROM attendance WHERE employee_id = ? AND date = ? AND (time_in IS NOT NULL OR time_out IS NOT NULL OR status_clock_out = 'Absent' OR Clock_In_Status = 'Absent')
                ) LIMIT 1";
        $ins = $conn->prepare($sql);
        $ins->execute([$empId, $shiftName, $shiftDate, 'Absent', 'Absent', $empId, $shiftDate]);
        if ($ins->rowCount()) $inserted++;
    }

    echo "Mark absent run complete. Inserted: {$inserted}\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}

?>