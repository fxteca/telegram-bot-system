<?php
/**
 * 管理员认证
 */

header('Content-Type: application/json');

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    require_once __DIR__ . '/../../config/config.php';
    $config = include __DIR__ . '/../../config/config.php';

    if ($action === 'login' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if ($username === $config['admin']['username'] && password_verify($password, $config['admin']['password_hash'])) {
            $_SESSION['admin'] = $username;
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => '登录成功']);
        } else {
            http_response_code(401);
            echo json_encode(['error' => '用户名或密码错误']);
        }
    } elseif ($action === 'logout' && $method === 'POST') {
        session_destroy();
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => '退出成功']);
    } elseif ($action === 'check' && $method === 'GET') {
        if (isset($_SESSION['admin'])) {
            http_response_code(200);
            echo json_encode(['authenticated' => true]);
        } else {
            http_response_code(401);
            echo json_encode(['authenticated' => false]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => '无效的请求']);
    }
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}