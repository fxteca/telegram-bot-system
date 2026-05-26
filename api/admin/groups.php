<?php
/**
 * 用户组管理 API
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

    switch ($action) {
        case 'list':
            if ($method === 'GET') {
                $botId = $_GET['bot_id'] ?? 0;
                
                $result = $db->query(
                    "SELECT * FROM user_groups WHERE bot_id = ? ORDER BY created_at DESC",
                    'i',
                    [$botId]
                );
                
                $groups = [];
                while ($row = $result->fetch_assoc()) {
                    $groups[] = $row;
                }
                echo json_encode(['success' => true, 'data' => $groups]);
            }
            break;

        case 'add':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $result = $db->execute(
                    "INSERT INTO user_groups (bot_id, group_name, group_description, price, duration_days, status) 
                     VALUES (?, ?, ?, ?, ?, ?)",
                    'issdii',
                    [$data['bot_id'], $data['group_name'], $data['group_description'], $data['price'], $data['duration_days'], 'active']
                );

                if ($result['success']) {
                    echo json_encode(['success' => true, 'id' => $result['insert_id']]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => '添加失败: ' . $result['error']]);
                }
            }
            break;

        case 'update':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $result = $db->execute(
                    "UPDATE user_groups SET group_name = ?, group_description = ?, price = ?, duration_days = ?, status = ? WHERE id = ?",
                    'ssdiis',
                    [$data['group_name'], $data['group_description'], $data['price'], $data['duration_days'], $data['status'], $data['id']]
                );

                echo json_encode(['success' => true]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => '未知操作']);
    }
    $db->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
exit;