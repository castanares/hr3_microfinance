<?php
// api/timesheets.php
header('Content-Type: application/json');
include 'db.php';
$method = $_SERVER['REQUEST_METHOD'];
try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) { $stmt = $conn->prepare('SELECT t.*, e.name as employee_name FROM timesheets t LEFT JOIN employees e ON t.employee_id = e.id WHERE t.id=?'); $stmt->execute([$_GET['id']]); echo json_encode($stmt->fetch(PDO::FETCH_ASSOC)); exit; }
        $stmt = $conn->query('SELECT t.*, e.name as employee_name FROM timesheets t LEFT JOIN employees e ON t.employee_id = e.id ORDER BY t.ts_date DESC'); echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)); exit;
    }
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $employee_name = $data['employee_name'] ?? $data['employee'] ?? null;
    $employee_id = $data['employee_id'] ?? null;
    $shift = $data['shift'] ?? $data['shift_type'] ?? null;
    $ts_date = $data['ts_date'] ?? $data['shift_date'] ?? null;
    if ($method === 'POST') {
        if (!$employee_id && $employee_name) { $s = $conn->prepare('SELECT id FROM employees WHERE name = ? LIMIT 1'); $s->execute([$employee_name]); $r = $s->fetch(PDO::FETCH_ASSOC); if ($r) $employee_id = $r['id']; }
        $stmt = $conn->prepare('INSERT INTO timesheets (employee_id, shift, ts_date, status) VALUES (?, ?, ?, ?)'); $stmt->execute([ $employee_id, $shift, $ts_date, $data['status'] ?? 'Pending' ]); echo json_encode(['id'=>$conn->lastInsertId()]); exit;
    }
    if ($method === 'PUT') { $id = $_GET['id'] ?? null; if(!$id){ http_response_code(400); echo json_encode(['error'=>'missing id']); exit; } if (!$employee_id && $employee_name) { $s = $conn->prepare('SELECT id FROM employees WHERE name = ? LIMIT 1'); $s->execute([$employee_name]); $r = $s->fetch(PDO::FETCH_ASSOC); if ($r) $employee_id = $r['id']; } $stmt = $conn->prepare('UPDATE timesheets SET employee_id=?, shift=?, ts_date=?, status=? WHERE id=?'); $stmt->execute([ $employee_id, $shift, $ts_date, $data['status'] ?? null, $id ]); echo json_encode(['ok'=>true]); exit; }
    if ($method === 'DELETE') { $id = $_GET['id'] ?? null; if(!$id){ http_response_code(400); echo json_encode(['error'=>'missing id']); exit; } $stmt = $conn->prepare('DELETE FROM timesheets WHERE id=?'); $stmt->execute([$id]); echo json_encode(['ok'=>true]); exit; }
    http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
} catch (PDOException $e) { http_response_code(500); echo json_encode(['error'=>$e->getMessage()]); }
?>
