<?php
// api/employees.php
header('Content-Type: application/json');
include 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $stmt = $conn->prepare('SELECT * FROM employees WHERE id = ?'); $stmt->execute([$_GET['id']]); echo json_encode($stmt->fetch(PDO::FETCH_ASSOC)); exit;
        }
        if (isset($_GET['rfid'])) {
            $stmt = $conn->prepare('SELECT * FROM employees WHERE rfid = ?'); $stmt->execute([$_GET['rfid']]); echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)); exit;
        }
        $stmt = $conn->query('SELECT * FROM employees ORDER BY name'); echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)); exit;
    }

    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    if ($method === 'POST') {
        $stmt = $conn->prepare('INSERT INTO employees (name, position, email, rfid, department) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([ $data['name'] ?? null, $data['position'] ?? null, $data['email'] ?? null, $data['rfid'] ?? null, $data['department'] ?? null ]);
        echo json_encode(['id'=>$conn->lastInsertId()]); exit;
    }

    if ($method === 'PUT') {
        $id = $_GET['id'] ?? null; if(!$id){ http_response_code(400); echo json_encode(['error'=>'missing id']); exit; }
        $stmt = $conn->prepare('UPDATE employees SET name=?, position=?, email=?, rfid=?, department=? WHERE id=?');
        $stmt->execute([ $data['name'] ?? null, $data['position'] ?? null, $data['email'] ?? null, $data['rfid'] ?? null, $data['department'] ?? null, $id ]);
        echo json_encode(['ok'=>true]); exit;
    }

    if ($method === 'DELETE') { $id = $_GET['id'] ?? null; if(!$id){ http_response_code(400); echo json_encode(['error'=>'missing id']); exit; } $stmt = $conn->prepare('DELETE FROM employees WHERE id=?'); $stmt->execute([$id]); echo json_encode(['ok'=>true]); exit; }

    http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
} catch (PDOException $e) { http_response_code(500); echo json_encode(['error'=>$e->getMessage()]); }
?>
