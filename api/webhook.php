<?php
/**
 * Telegram Webhook 处理
 */

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../core/Database.php';
    require_once __DIR__ . '/../core/TelegramBot.php';

    $config = include __DIR__ . '/../config/config.php';
    $db = new Database($config);

    // 获取 POST 数据
    $input = file_get_contents('php://input');
    $update = json_decode($input, true);

    if (!$update) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    // 获取 bot_token 和 bot_id
    $botToken = $_GET['bot_token'] ?? '';
    $botId = $_GET['bot_id'] ?? 0;

    if (!$botToken || !$botId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing parameters']);
        exit;
    }

    // 验证 bot 是否存在
    $result = $db->query(
        "SELECT id FROM bots WHERE bot_token = ? AND id = ?",
        'si',
        [$botToken, $botId]
    );

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // 处理更新
    $bot = new TelegramBot($botToken, $botId, $db, $config);
    $bot->handleUpdate($update);

    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}