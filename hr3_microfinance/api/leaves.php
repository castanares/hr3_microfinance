<?php
// api/leaves.php - RESTful endpoints for leaves
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

// support method override when clients send multipart/form-data (browsers don't populate $_FILES on true PUT)
// e.g., send POST with _method=PUT or X-HTTP-Method-Override header
if ($method === 'POST') {
    if (isset($_POST['_method']) && !empty($_POST['_method'])) {
        $method = strtoupper($_POST['_method']);
    } elseif (!empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
        $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
    }
}

// helper: read JSON body or fallback to $_POST
function get_json_body() {
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) return $data;
    return $_POST;
}

// helper: ensure employee exists, return employee_id. If employee_name provided and no match, create.
function ensure_employee($conn, $employeeNameOrId) {
    if (!$employeeNameOrId) return null;
    // if it's numeric, assume ID
    if (is_numeric($employeeNameOrId)) return (int)$employeeNameOrId;
    // try to find by name
    $stmt = $conn->prepare('SELECT id FROM employees WHERE name = ? LIMIT 1');
    $stmt->execute([$employeeNameOrId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) return (int)$row['id'];
    // create a minimal employee record (name only)
    $stmt = $conn->prepare('INSERT INTO employees (name) VALUES (?)');
    $stmt->execute([$employeeNameOrId]);
    return (int)$conn->lastInsertId();
}

try {
    if ($method === 'GET') {
        // GET single by id
        if (isset($_GET['id'])) {
            // map existing 'reason' column to 'leave_type' for frontend compatibility
            $stmt = $conn->prepare('SELECT l.id, l.employee_id, e.name as employee_name, l.reason AS leave_type, l.start_date as from_date, l.end_date as to_date, l.status, l.leave_letter FROM leaves l LEFT JOIN employees e ON l.employee_id = e.id WHERE l.id = ? LIMIT 1');
            $stmt->execute([$_GET['id']]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            echo json_encode($res);
            exit;
        }

        // GET by employee_id
        if (isset($_GET['employee_id'])) {
            $stmt = $conn->prepare('SELECT l.id, l.employee_id, e.name as employee_name, l.reason AS leave_type, l.start_date as from_date, l.end_date as to_date, l.status, l.leave_letter FROM leaves l LEFT JOIN employees e ON l.employee_id = e.id WHERE l.employee_id = ? ORDER BY l.start_date DESC');
            $stmt->execute([$_GET['employee_id']]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }

        // GET all
        $stmt = $conn->query('SELECT l.id, l.employee_id, e.name as employee_name, l.reason AS leave_type, l.start_date as from_date, l.end_date as to_date, l.status, l.leave_letter FROM leaves l LEFT JOIN employees e ON l.employee_id = e.id ORDER BY l.start_date DESC');
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    $data = get_json_body();

    // handle file upload if multipart/form-data
    $uploadDir = __DIR__ . '/../uploads/leave_letters/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

    if ($method === 'POST') {
        $fileName = null;
        if (!empty($_FILES['leave_letter']) && $_FILES['leave_letter']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['leave_letter']['tmp_name'];
            $orig = basename($_FILES['leave_letter']['name']);
            $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
            move_uploaded_file($tmp, $uploadDir . $fileName);
        }
        // allow JSON body or form fields
        $data = get_json_body();
        // expected keys from frontend: employee_name, leave_type, from_date, to_date
        $employeeKey = $data['employee_id'] ?? $data['employee_name'] ?? $_POST['employee_id'] ?? $_POST['employee_name'] ?? null;
        $employee_id = ensure_employee($conn, $employeeKey);
        $leave_type = $data['leave_type'] ?? $data['reason'] ?? $_POST['leave_type'] ?? $_POST['reason'] ?? null;
        $from = $data['from_date'] ?? $data['start_date'] ?? $_POST['from_date'] ?? $_POST['start_date'] ?? null;
        $to = $data['to_date'] ?? $data['end_date'] ?? $_POST['to_date'] ?? $_POST['end_date'] ?? null;
        $status = $data['status'] ?? $_POST['status'] ?? 'Pending';

        if (!$employee_id || !$leave_type || !$from || !$to) {
            http_response_code(400);
            echo json_encode(['error' => 'missing required fields']);
            exit;
        }

    // use existing 'reason' column in the leaves table
        $stmt = $conn->prepare('INSERT INTO leaves (employee_id, reason, start_date, end_date, status, leave_letter) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$employee_id, $leave_type, $from, $to, $status, $fileName]);
        echo json_encode(['id' => $conn->lastInsertId()]);
        exit;
    }

    if ($method === 'PUT') {
        $id = $_GET['id'] ?? null;
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'missing id']); exit; }
        // handle file replace in PUT if provided via $_FILES
        $fileName = null;
        if (!empty($_FILES['leave_letter']) && $_FILES['leave_letter']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['leave_letter']['tmp_name'];
            $orig = basename($_FILES['leave_letter']['name']);
            $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
            move_uploaded_file($tmp, $uploadDir . $fileName);
            // optional: remove old file later
        }

        $employeeKey = $data['employee_id'] ?? $data['employee_name'] ?? $_POST['employee_id'] ?? $_POST['employee_name'] ?? null;
        $employee_id = $employeeKey ? ensure_employee($conn, $employeeKey) : null;
        $leave_type = $data['leave_type'] ?? $data['reason'] ?? $_POST['leave_type'] ?? $_POST['reason'] ?? null;
        $from = $data['from_date'] ?? $data['start_date'] ?? $_POST['from_date'] ?? $_POST['start_date'] ?? null;
        $to = $data['to_date'] ?? $data['end_date'] ?? $_POST['to_date'] ?? $_POST['end_date'] ?? null;
        $status = $data['status'] ?? $_POST['status'] ?? null;

        // Build update dynamically
        $fields = [];
        $params = [];
        if ($employee_id) { $fields[] = 'employee_id = ?'; $params[] = $employee_id; }
    if ($leave_type) { $fields[] = 'reason = ?'; $params[] = $leave_type; }
        if ($from) { $fields[] = 'start_date = ?'; $params[] = $from; }
        if ($to) { $fields[] = 'end_date = ?'; $params[] = $to; }
    if ($status) { $fields[] = 'status = ?'; $params[] = $status; }
    if ($fileName) { $fields[] = 'leave_letter = ?'; $params[] = $fileName; }
        if (empty($fields)) { http_response_code(400); echo json_encode(['error'=>'no fields to update']); exit; }

        $params[] = $id;
        $sql = 'UPDATE leaves SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'missing id']); exit; }
        $stmt = $conn->prepare('DELETE FROM leaves WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error'=>'Method not allowed']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
}

?>
