<?php
// api/claims.php - RESTful claims API
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

// helper: read JSON body or fallback to $_POST
function get_json_body() {
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) return $data;
    return $_POST;
}

// helper: ensure employee exists (by id or name), create minimal record if name given
function ensure_employee($conn, $employeeNameOrId) {
    if (!$employeeNameOrId) return null;
    if (is_numeric($employeeNameOrId)) return (int)$employeeNameOrId;
    $stmt = $conn->prepare('SELECT id FROM employees WHERE name = ? LIMIT 1');
    $stmt->execute([$employeeNameOrId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) return (int)$row['id'];
    $stmt = $conn->prepare('INSERT INTO employees (name) VALUES (?)');
    $stmt->execute([$employeeNameOrId]);
    return (int)$conn->lastInsertId();
}

try {
    if ($method === 'GET') {
        // single
        if (isset($_GET['id'])) {
            $stmt = $conn->prepare('SELECT c.id, c.employee_id, e.name as employee_name, c.description AS claim_type, DATE(c.created_at) AS claim_date, c.amount, c.status FROM claims c LEFT JOIN employees e ON c.employee_id = e.id WHERE c.id = ? LIMIT 1');
            $stmt->execute([$_GET['id']]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: []);
            exit;
        }

        // all
        $stmt = $conn->query('SELECT c.id, c.employee_id, e.name as employee_name, c.description AS claim_type, DATE(c.created_at) AS claim_date, c.amount, c.status FROM claims c LEFT JOIN employees e ON c.employee_id = e.id ORDER BY c.created_at DESC');
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    $data = get_json_body();

    if ($method === 'POST') {
        // accept employee_name or employee_id
        $employeeKey = $data['employee_id'] ?? $data['employee_name'] ?? $_POST['employee_id'] ?? $_POST['employee_name'] ?? null;
        $employee_id = ensure_employee($conn, $employeeKey);
        $claim_type = $data['claim_type'] ?? $data['description'] ?? $_POST['claim_type'] ?? $_POST['description'] ?? null;
        $amount = $data['amount'] ?? $_POST['amount'] ?? 0;
        $claim_date = $data['claim_date'] ?? $_POST['claim_date'] ?? null;
        $status = $data['status'] ?? $_POST['status'] ?? 'Pending';

        if (!$employee_id) { http_response_code(400); echo json_encode(['error'=>'missing employee']); exit; }

        // store claim_type in description, created_at set to claim_date if provided else NOW()
        if ($claim_date) {
            $stmt = $conn->prepare('INSERT INTO claims (employee_id, amount, description, status, created_at) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$employee_id, $amount, $claim_type, $status, $claim_date]);
        } else {
            $stmt = $conn->prepare('INSERT INTO claims (employee_id, amount, description, status) VALUES (?, ?, ?, ?)');
            $stmt->execute([$employee_id, $amount, $claim_type, $status]);
        }
        echo json_encode(['id' => $conn->lastInsertId(), 'message' => 'Claim created']);
        exit;
    }

    if ($method === 'PUT') {
        $id = $_GET['id'] ?? null;
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'missing id']); exit; }

        $employeeKey = $data['employee_id'] ?? $data['employee_name'] ?? $_POST['employee_id'] ?? $_POST['employee_name'] ?? null;
        $employee_id = $employeeKey ? ensure_employee($conn, $employeeKey) : null;
        $claim_type = $data['claim_type'] ?? $data['description'] ?? $_POST['claim_type'] ?? $_POST['description'] ?? null;
        $amount = $data['amount'] ?? $_POST['amount'] ?? null;
        $claim_date = $data['claim_date'] ?? $_POST['claim_date'] ?? null;
        $status = $data['status'] ?? $_POST['status'] ?? null;

        $fields = [];
        $params = [];
        if ($employee_id) { $fields[] = 'employee_id = ?'; $params[] = $employee_id; }
        if ($amount !== null) { $fields[] = 'amount = ?'; $params[] = $amount; }
        if ($claim_type) { $fields[] = 'description = ?'; $params[] = $claim_type; }
        if ($status) { $fields[] = 'status = ?'; $params[] = $status; }
        if ($claim_date) { $fields[] = 'created_at = ?'; $params[] = $claim_date; }

        if (empty($fields)) { http_response_code(400); echo json_encode(['error'=>'no fields to update']); exit; }

        $params[] = $id;
        $sql = 'UPDATE claims SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['ok' => true, 'message' => 'Claim updated']);
        exit;
    }

    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'missing id']); exit; }
        $stmt = $conn->prepare('DELETE FROM claims WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode(['ok' => true, 'message' => 'Claim deleted']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error'=>'Method not allowed']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
