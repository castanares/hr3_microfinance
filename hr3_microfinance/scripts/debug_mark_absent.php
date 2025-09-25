<?php
// scripts/debug_mark_absent.php
// Quick diagnostic: prints today's shifts, parsed end times, and attendance counts
require_once __DIR__ . '/../api/db.php';

// Force Manila timezone so CLI diagnostics match the app
date_default_timezone_set('Asia/Manila');

$now = new DateTime('now');
$today = $now->format('Y-m-d');

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

$now = new DateTime('now');
$today = $now->format('Y-m-d');

echo "Now: " . $now->format('Y-m-d H:i:s') . "\n";

$stmt = $conn->prepare('SELECT * FROM shifts WHERE date = ?');
$stmt->execute([$today]);
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$shifts) {
    echo "No shifts found for today ($today)\n";
    exit(0);
}

foreach ($shifts as $s) {
    $id = $s['id'] ?? '(no id)';
    $empId = $s['employee_id'] ?? $s['emp_id'] ?? '(no emp)';
    $shiftDate = $s['date'] ?? '(no date)';
    $endTime = $s['end_time'] ?? $s['time_end'] ?? $s['end'] ?? null;
    $shiftName = $s['shift_name'] ?? $s['shift'] ?? null;

    $endDt = parse_shift_time($shiftDate, $endTime);
    $endStr = $endDt ? $endDt->format('Y-m-d H:i:s') : '(unparsed)';
    $isPast = $endDt ? ($now > $endDt ? 'YES' : 'NO') : '(unknown)';

    // attendance check
    $check = $conn->prepare("SELECT COUNT(*) as c FROM attendance WHERE employee_id = ? AND date = ? AND (time_in IS NOT NULL OR time_out IS NOT NULL OR status_clock_out = 'Absent' OR Clock_In_Status = 'Absent')");
    $check->execute([$empId, $shiftDate]);
    $c = $check->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;

    echo "--- Shift ID: $id | Emp: $empId | Name: " . ($shiftName ?? '-') . " | Date: $shiftDate\n";
    echo "    end_time raw: " . var_export($endTime, true) . "\n";
    echo "    parsed end: $endStr | past? $isPast\n";
    echo "    attendance rows count (has clocks or already absent): $c\n";

    if ($c > 0) {
        $rows = $conn->prepare('SELECT * FROM attendance WHERE employee_id = ? AND date = ?');
        $rows->execute([$empId, $shiftDate]);
        $att = $rows->fetchAll(PDO::FETCH_ASSOC);
        foreach ($att as $ar) {
            echo "      - attendance id: " . ($ar['id'] ?? '(no id)') . " time_in:" . ($ar['time_in'] ?? 'NULL') . " time_out:" . ($ar['time_out'] ?? 'NULL') . " Clock_In_Status:" . ($ar['Clock_In_Status'] ?? '') . " status_clock_out:" . ($ar['status_clock_out'] ?? '') . "\n";
        }
    }
}

echo "Debug complete.\n";

?>