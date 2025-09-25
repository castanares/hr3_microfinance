<?php

header('Content-Type: application/json');
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $conn->prepare("SELECT s.*, e.name as employee_name FROM shifts s LEFT JOIN employees e ON s.employee_id = e.id");
    $stmt->execute();
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($shifts);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $employee_id = $data['employee_id'] ?? '';
    $shift_name = $data['shift_name'] ?? '';
    $department = $data['department'] ?? '';
    $start_time = $data['start_time'] ?? '';
    $end_time = $data['end_time'] ?? '';
    $date = $data['date'] ?? '';

    if (!$employee_id || !$shift_name || !$start_time || !$end_time || !$date) {
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO shifts (employee_id, shift_name, department, start_time, end_time, date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$employee_id, $shift_name, $department, $start_time, $end_time, $date]);
    echo json_encode(['id' => $conn->lastInsertId()]);
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $_GET['id'] ?? '';
    $employee_id = $data['employee_id'] ?? '';
    $shift_name = $data['shift_name'] ?? '';
    $department = $data['department'] ?? '';
    $start_time = $data['start_time'] ?? '';
    $end_time = $data['end_time'] ?? '';
    $date = $data['date'] ?? '';

    if (!$id || !$employee_id || !$shift_name || !$start_time || !$end_time || !$date) {
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE shifts SET employee_id = ?, shift_name = ?, department = ?, start_time = ?, end_time = ?, date = ? WHERE id = ?");
    $stmt->execute([$employee_id, $shift_name, $department, $start_time, $end_time, $date, $id]);
    echo json_encode(['ok' => true]);
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    if (!$id) {
        echo json_encode(['error' => 'Missing ID']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM shifts WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['error' => 'Method not allowed']);
}
?>