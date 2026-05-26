<?php
/**
 * 支付记录 API
 */

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    exit(json_encode(['error' => '未授权']));
}

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../core/Database.php';

    $config = include __DIR__ . '/../../config/config.php';
    $db = new Database($config);

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    if ($action === 'list' && $method === 'GET') {
        $result = $db->query(
            "SELECT p.*, ug.group_name, b.bot_name 
             FROM payments p
             JOIN user_groups ug ON p.group_id = ug.id
             JOIN bots b ON p.bot_id = b.id
             ORDER BY p.created_at DESC LIMIT 100"
        );
        
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $payments]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => '未知操作']);
    }
    $db->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
exit;