<?php
// api/attendance.php
header('Content-Type: application/json');
include 'db.php';

// Ensure server uses a consistent timezone. Change this to your local timezone if needed.
if (!ini_get('date.timezone')) {
    // Default to Manila (UTC+8) which you can change to match your server location
    date_default_timezone_set('Asia/Manila');
}

$method = $_SERVER['REQUEST_METHOD'];
try {
    if ($method === 'GET') {
        // optional filters
        if (isset($_GET['id'])) {
            $stmt = $conn->prepare('SELECT a.*, e.name as employee_name FROM attendance a LEFT JOIN employees e ON a.employee_id = e.id WHERE a.id = ?');
            $stmt->execute([$_GET['id']]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
            exit;
        }
        // Helper to parse shift time strings into DateTime on the given date
        function parse_shift_time($date, $timeStr) {
            if (!$timeStr) return null;
            // If already a full datetime, try to parse
            if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{1,2}:\d{2}/', $timeStr)) {
                $d = DateTime::createFromFormat('Y-m-d H:i:s', $timeStr) ?: DateTime::createFromFormat('Y-m-d H:i', $timeStr);
                if ($d) return $d;
            }
            // Try with AM/PM
            $d = DateTime::createFromFormat('Y-m-d g:i A', $date . ' ' . $timeStr);
            if ($d) return $d;
            // Try 24hr
            $d = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $timeStr);
            if ($d) return $d;
            // Fallback: try strtotime on combined
            $ts = strtotime($date . ' ' . $timeStr);
            if ($ts === false) return null;
            return (new DateTime())->setTimestamp($ts);
        }

        if (isset($_GET['employee_id']) && isset($_GET['date'])) {
            $employee_id = $_GET['employee_id'];
            $date = $_GET['date'];

            // If there's a shift for this employee/date and the shift end time has passed,
            // and there is no attendance recorded (or no clock-in/out), insert an Absent row.
            try {
                $now = new DateTime('now');
                $shiftStmt = $conn->prepare('SELECT * FROM shifts WHERE employee_id = ? AND date = ?');
                $shiftStmt->execute([$employee_id, $date]);
                $shifts = $shiftStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($shifts as $shiftRow) {
                    $endTime = $shiftRow['end_time'] ?? $shiftRow['time_end'] ?? $shiftRow['end'] ?? null;
                    $endDt = parse_shift_time($date, $endTime);
                    if (!$endDt) continue;
                    if ($now <= $endDt) continue; // shift not yet ended

                    // Check if attendance exists for this employee/date with any clock or already marked Absent
                    $check = $conn->prepare("SELECT COUNT(*) as c FROM attendance WHERE employee_id = ? AND date = ? AND (time_in IS NOT NULL OR time_out IS NOT NULL OR status_clock_out = 'Absent' OR Clock_In_Status = 'Absent')");
                    $check->execute([$employee_id, $date]);
                    $c = $check->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
                    if ($c > 0) continue;

                    // Atomically insert absent record only if none exists to avoid races
                    $shiftName = $shiftRow['shift_name'] ?? $shiftRow['shift'] ?? null;
                    $sql = "INSERT INTO attendance (employee_id, shift, date, rfid, time_in, Clock_In_Status, time_out, status_clock_out)
                            SELECT ?, ?, ?, NULL, NULL, ?, NULL, ?
                            FROM DUAL
                            WHERE NOT EXISTS (
                                SELECT 1 FROM attendance WHERE employee_id = ? AND date = ? AND (time_in IS NOT NULL OR time_out IS NOT NULL OR status_clock_out = 'Absent' OR Clock_In_Status = 'Absent')
                            ) LIMIT 1";
                    $ins = $conn->prepare($sql);
                    $ins->execute([$employee_id, $shiftName, $date, 'Absent', 'Absent', $employee_id, $date]);
                }
            } catch (Exception $e) {
                // don't block; continue to return attendance
            }

            $stmt = $conn->prepare('SELECT a.*, e.name as employee_name FROM attendance a LEFT JOIN employees e ON a.employee_id = e.id WHERE a.employee_id = ? AND a.date = ?');
            $stmt->execute([$employee_id, $date]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }
        if (isset($_GET['date'])) {
            $date = $_GET['date'];

            // Auto-insert absent rows for any shifts on this date whose end time has passed and
            // where the employee has no attendance recorded.
            try {
                $now = new DateTime('now');
                $shiftStmt = $conn->prepare('SELECT * FROM shifts WHERE date = ?');
                $shiftStmt->execute([$date]);
                $shifts = $shiftStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($shifts as $shiftRow) {
                    $empId = $shiftRow['employee_id'] ?? $shiftRow['emp_id'] ?? null;
                    if (!$empId) continue;
                    $endTime = $shiftRow['end_time'] ?? $shiftRow['time_end'] ?? $shiftRow['end'] ?? null;
                    $endDt = parse_shift_time($date, $endTime);
                    if (!$endDt) continue;
                    if ($now <= $endDt) continue; // shift not ended

                    // Check if attendance exists for this employee/date with any clock or already marked Absent
                    $check = $conn->prepare("SELECT COUNT(*) as c FROM attendance WHERE employee_id = ? AND date = ? AND (time_in IS NOT NULL OR time_out IS NOT NULL OR status_clock_out = 'Absent' OR Clock_In_Status = 'Absent')");
                    $check->execute([$empId, $date]);
                    $c = $check->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
                    if ($c > 0) continue;

                    // Atomically insert absent record only if none exists to avoid races
                    $shiftName = $shiftRow['shift_name'] ?? $shiftRow['shift'] ?? null;
                    $sql = "INSERT INTO attendance (employee_id, shift, date, rfid, time_in, Clock_In_Status, time_out, status_clock_out)
                            SELECT ?, ?, ?, NULL, NULL, ?, NULL, ?
                            FROM DUAL
                            WHERE NOT EXISTS (
                                SELECT 1 FROM attendance WHERE employee_id = ? AND date = ? AND (time_in IS NOT NULL OR time_out IS NOT NULL OR status_clock_out = 'Absent' OR Clock_In_Status = 'Absent')
                            ) LIMIT 1";
                    $ins = $conn->prepare($sql);
                    $ins->execute([$empId, $shiftName, $date, 'Absent', 'Absent', $empId, $date]);
                }
            } catch (Exception $e) {
                // ignore insertion errors and continue to return data
            }

            $stmt = $conn->prepare('SELECT a.*, e.name as employee_name FROM attendance a LEFT JOIN employees e ON a.employee_id = e.id WHERE a.date = ? ORDER BY a.time_in DESC');
            $stmt->execute([$date]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }
        $stmt = $conn->query('SELECT a.*, e.name as employee_name FROM attendance a LEFT JOIN employees e ON a.employee_id = e.id ORDER BY a.time_in DESC');
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    if ($method === 'POST') {
        $employee_id = $data['employee_id'] ?? null;
        $shift = $data['shift'] ?? null;
        $date = $data['date'] ?? null;
        $rfid = $data['rfid'] ?? null;
        $time_in = $data['time_in'] ?? null; // client-provided timestamp (may be ignored)
        $time_out = $data['time_out'] ?? null; // client-provided timestamp (may be ignored)
        $client_tz = $data['client_tz'] ?? null;

        // If client provided a timezone, and it's a valid timezone identifier, apply it for this request
        $applied_tz = null;
        if ($client_tz) {
            $allTz = timezone_identifiers_list();
            if (in_array($client_tz, $allTz, true)) {
                date_default_timezone_set($client_tz);
                $applied_tz = $client_tz;
            }
        }

        if (!$employee_id || !$date) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing employee_id or date']);
            exit;
        }

        // Find shift for this employee & date to compute statuses server-side
        $shiftStmt = $conn->prepare('SELECT * FROM shifts WHERE employee_id = ? AND date = ? LIMIT 1');
        $shiftStmt->execute([$employee_id, $date]);
        $shiftRow = $shiftStmt->fetch(PDO::FETCH_ASSOC);

        // If there's no shift for this employee on the given date, reject the request.
        if (!$shiftRow) {
            http_response_code(400);
            echo json_encode(['error' => 'No shift found for employee on this date', 'can_clock' => false]);
            exit;
        }

    // Server time now (may use client tz if applied)
    $now = new DateTime('now');
    $nowStr = $now->format('Y-m-d H:i:s');
    $server_timezone = date_default_timezone_get();

        // Helper to parse shift time strings ("HH:MM" or "H:MM PM") into DateTime on the given date
        $parseShiftTime = function($timeStr) use ($date) {
            if (!$timeStr) return null;
            // If already a full datetime, try to parse
            if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{1,2}:\d{2}/', $timeStr)) {
                return DateTime::createFromFormat('Y-m-d H:i:s', $timeStr) ?: DateTime::createFromFormat('Y-m-d H:i', $timeStr);
            }
            // Try with AM/PM
            $d = DateTime::createFromFormat('Y-m-d g:i A', $date . ' ' . $timeStr);
            if ($d) return $d;
            // Try 24hr
            $d = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $timeStr);
            if ($d) return $d;
            // Fallback: try strtotime on combined
            $ts = strtotime($date . ' ' . $timeStr);
            if ($ts === false) return null;
            return (new DateTime())->setTimestamp($ts);
        };

        // Compute desired server-side values
        $server_time_in = null;
        $server_time_out = null;
        $server_clock_in_status = null;
        $server_status_clock_out = null;

        // Honor explicit attendance_id from client if provided to avoid duplicates
        $attendance_id = $data['attendance_id'] ?? null;
        if ($attendance_id) {
            $stmt = $conn->prepare('SELECT id, time_in, time_out FROM attendance WHERE id = ? AND employee_id = ? AND date = ?');
            $stmt->execute([$attendance_id, $employee_id, $date]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            // If attendance_id was provided but not found (race or stale id), try to find any open attendance for this employee/date
            if (!$existing) {
                $stmt2 = $conn->prepare('SELECT id, time_in, time_out FROM attendance WHERE employee_id = ? AND date = ? AND time_in IS NOT NULL AND time_out IS NULL LIMIT 1');
                $stmt2->execute([$employee_id, $date]);
                $existing = $stmt2->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        } else {
            // Check if record exists (match by employee/date and optionally shift)
            $query = 'SELECT id, time_in, time_out FROM attendance WHERE employee_id = ? AND date = ?';
            $params = [$employee_id, $date];
            if ($shift) { $query .= ' AND shift = ?'; $params[] = $shift; }
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Additional safety: if we still don't have an appropriate existing row, try to find any open attendance
        // (time_in present and time_out null) for this employee/date and prefer it. This prevents duplicate inserts
        // when shift names mismatch or client-provided attendance_id was stale.
        if (!$existing || empty($existing['time_in']) || !empty($existing['time_out'])) {
            $openStmt = $conn->prepare('SELECT id, time_in, time_out FROM attendance WHERE employee_id = ? AND date = ? AND time_in IS NOT NULL AND time_out IS NULL LIMIT 1');
            $openStmt->execute([$employee_id, $date]);
            $openRow = $openStmt->fetch(PDO::FETCH_ASSOC);
            if ($openRow) {
                $existing = $openRow;
            }
        }

        if (!$existing || !$existing['time_in']) {
            // This is a clock-in event. Use server time as time_in regardless of client payload.
            $server_time_in = $nowStr;
            // Default status
            $server_clock_in_status = 'On Time';
            if ($shiftRow && !empty($shiftRow['start_time'])) {
                $startDt = $parseShiftTime($shiftRow['start_time']);
                if ($startDt) {
                    // late if now > start + 15 minutes
                    $lateThreshold = clone $startDt;
                    $lateThreshold->modify('+15 minutes');
                    if ($now > $lateThreshold) $server_clock_in_status = 'Late';
                }
            }
            // Prepare insert/update payload
                if ($existing) {
                // update existing row with time_in and status
                    // Prefer server-side stored shift name when available
                    $serverShiftName = $shiftRow['shift_name'] ?? $shiftRow['shift'] ?? $shift;
                    $u = $conn->prepare('UPDATE attendance SET time_in = ?, Clock_In_Status = ?, rfid = ?, shift = ? WHERE id = ?');
                    $u->execute([$server_time_in, $server_clock_in_status, $rfid, $serverShiftName, $existing['id']]);
                echo json_encode(['success' => true, 'id' => $existing['id'], 'time_in' => $server_time_in, 'Clock_In_Status' => $server_clock_in_status, 'server_now' => $nowStr, 'server_timezone' => $server_timezone]);
            } else {
                    // Before inserting, check again if any open attendance exists for this employee/date
                    // (race conditions, mismatched shift names). If found, prefer updating it as clock-out.
                    $openStmt = $conn->prepare('SELECT id, time_in, time_out FROM attendance WHERE employee_id = ? AND date = ? AND time_in IS NOT NULL AND time_out IS NULL LIMIT 1');
                    $openStmt->execute([$employee_id, $date]);
                    $openRow = $openStmt->fetch(PDO::FETCH_ASSOC);
                    if ($openRow) {
                        // Treat as clock-out for the open record
                        $server_time_out = $nowStr;
                        $server_status_clock_out = 'Present';
                        $u2 = $conn->prepare('UPDATE attendance SET time_out = ?, status_clock_out = ? WHERE id = ?');
                        $u2->execute([$server_time_out, $server_status_clock_out, $openRow['id']]);
                        echo json_encode(['success' => true, 'action' => 'clock_out', 'id' => $openRow['id'], 'time_out' => $server_time_out, 'status_clock_out' => $server_status_clock_out, 'server_now' => $nowStr, 'server_timezone' => $server_timezone]);
                    } else {
                    // Prefer server-side stored shift name when inserting
                    $serverShiftName = $shiftRow['shift_name'] ?? $shiftRow['shift'] ?? $shift;
                    $ins = $conn->prepare('INSERT INTO attendance (employee_id, shift, date, rfid, time_in, Clock_In_Status, time_out, status_clock_out) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)');
                    $ins->execute([$employee_id, $serverShiftName, $date, $rfid, $server_time_in, $server_clock_in_status]);
                echo json_encode(['success' => true, 'action' => 'clock_in', 'id' => $conn->lastInsertId(), 'time_in' => $server_time_in, 'Clock_In_Status' => $server_clock_in_status, 'server_now' => $nowStr, 'server_timezone' => $server_timezone]);
                    }
            }
            exit;
        } else {
            // This is a clock-out attempt. Only allow if server time >= shift end (if shift defined)
            if ($shiftRow && !empty($shiftRow['end_time'])) {
                $endDt = $parseShiftTime($shiftRow['end_time']);
                if ($endDt && $now < $endDt) {
                    // Block early clock-out
                        http_response_code(400);
                        echo json_encode(['error' => 'Shift not ended yet', 'can_clock_out' => false, 'server_now' => $nowStr, 'shift_end' => $endDt->format('Y-m-d H:i:s'), 'server_timezone' => $server_timezone]);
                    exit;
                }
            }
            // Accept clock out, set server time_out and compute status
            // For now, treat allowed clock-outs as 'Present' regardless of leaving after end time.
            $server_time_out = $nowStr;
            $server_status_clock_out = 'Present';
            // Update record
            $u = $conn->prepare('UPDATE attendance SET time_out = ?, status_clock_out = ? WHERE id = ?');
            $u->execute([$server_time_out, $server_status_clock_out, $existing['id']]);
            echo json_encode(['success' => true, 'id' => $existing['id'], 'time_out' => $server_time_out, 'status_clock_out' => $server_status_clock_out, 'server_now' => $nowStr, 'server_timezone' => $server_timezone]);
            exit;
        }
    }

    if ($method === 'PUT') {
        $id = $_GET['id'] ?? null;
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'missing id']); exit; }
        $employee_id = $data['employee_id'] ?? null;
        $shift = $data['shift'] ?? null;
        $date = $data['date'] ?? null;
        $rfid = $data['rfid'] ?? null;
        $time_in = $data['time_in'] ?? null;
        $Clock_In_Status = $data['Clock_In_Status'] ?? null;
        $time_out = $data['time_out'] ?? null;
        $status_clock_out = $data['status_clock_out'] ?? null;
        $stmt = $conn->prepare('UPDATE attendance SET employee_id=?, shift=?, date=?, rfid=?, time_in=?, Clock_In_Status=?, time_out=?, status_clock_out=? WHERE id=?');
        $stmt->execute([$employee_id, $shift, $date, $rfid, $time_in, $Clock_In_Status, $time_out, $status_clock_out, $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'missing id']); exit; }
        $stmt = $conn->prepare('DELETE FROM attendance WHERE id=?'); $stmt->execute([$id]); echo json_encode(['ok'=>true]); exit;
    }

    http_response_code(405); echo json_encode(['error'=>'Method not allowed']);

} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['error'=>$e->getMessage()]);
}
?>
