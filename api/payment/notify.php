<?php
/**
 * 支付回调处理
 */

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../core/Database.php';
    require_once __DIR__ . '/../../core/PaymentProcessor.php';

    $config = include __DIR__ . '/../../config/config.php';
    $db = new Database($config);
    $processor = new PaymentProcessor($db, $config);

    if ($processor->handleNotify($_POST)) {
        echo 'success';
    } else {
        echo 'fail';
    }
    exit;
} catch (Exception $e) {
    echo 'error';
    exit;
}