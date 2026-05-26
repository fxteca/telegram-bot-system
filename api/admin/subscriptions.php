<?php
/**
 * 订阅管理 API
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
                $result = $db->query(
                    "SELECT us.*, ug.group_name, b.bot_name 
                     FROM user_subscriptions us
                     JOIN user_groups ug ON us.group_id = ug.id
                     JOIN bots b ON us.bot_id = b.id
                     ORDER BY us.created_at DESC LIMIT 100"
                );
                
                $subscriptions = [];
                while ($row = $result->fetch_assoc()) {
                    $subscriptions[] = $row;
                }
                echo json_encode(['success' => true, 'data' => $subscriptions]);
            }
            break;

        case 'extend':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                // 获取用户组的时长
                $groupResult = $db->query(
                    "SELECT duration_days FROM user_groups WHERE id = ?",
                    'i',
                    [$data['group_id']]
                );
                
                if ($groupResult->num_rows === 0) {
                    throw new Exception('用户组不存在');
                }
                
                $group = $groupResult->fetch_assoc();
                $newExpiryDate = date('Y-m-d H:i:s', strtotime('+' . $group['duration_days'] . ' days'));
                
                $result = $db->execute(
                    "UPDATE user_subscriptions SET expiry_date = ?, updated_at = NOW() WHERE id = ?",
                    'si',
                    [$newExpiryDate, $data['id']]
                );

                echo json_encode(['success' => true, 'new_expiry' => $newExpiryDate]);
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