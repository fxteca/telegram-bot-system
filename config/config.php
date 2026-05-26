<?php
/**
 * 配置文件
 */

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'user' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'database' => getenv('DB_NAME') ?: 'telegram_bot_system',
        'charset' => 'utf8mb4'
    ],
    'yifu_pay' => [
        'enabled' => true,
        'partner_id' => getenv('YIFU_PARTNER_ID') ?: 'your_partner_id',
        'partner_key' => getenv('YIFU_PARTNER_KEY') ?: 'your_partner_key',
        'notify_url' => getenv('YIFU_NOTIFY_URL') ?: 'https://yourdomain.com/api/payment/notify.php',
        'return_url' => getenv('YIFU_RETURN_URL') ?: 'https://yourdomain.com/api/payment/return.php'
    ],
    'telegram' => [
        'api_url' => 'https://api.telegram.org',
        'timeout' => 10
    ],
    'admin' => [
        'username' => getenv('ADMIN_USERNAME') ?: 'admin',
        'password_hash' => getenv('ADMIN_PASSWORD_HASH') ?: password_hash('admin123', PASSWORD_BCRYPT)
    ],
    'app' => [
        'debug' => getenv('APP_DEBUG') ?: false,
        'timezone' => 'Asia/Shanghai'
    ]
];