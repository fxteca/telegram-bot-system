<?php
/**
 * 机器人管理 API
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
                $result = $db->query("SELECT * FROM bots ORDER BY created_at DESC");
                $bots = [];
                while ($row = $result->fetch_assoc()) {
                    $bots[] = $row;
                }
                echo json_encode(['success' => true, 'data' => $bots]);
            }
            break;

        case 'add':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $result = $db->execute(
                    "INSERT INTO bots (bot_name, bot_token, bot_id, status) VALUES (?, ?, ?, ?)",
                    'ssss',
                    [$data['bot_name'], $data['bot_token'], $data['bot_id'], 'active']
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
                    "UPDATE bots SET bot_name = ?, status = ?, updated_at = NOW() WHERE id = ?",
                    'ssi',
                    [$data['bot_name'], $data['status'], $data['id']]
                );

                echo json_encode(['success' => true]);
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $result = $db->execute(
                    "DELETE FROM bots WHERE id = ?",
                    'i',
                    [$data['id']]
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