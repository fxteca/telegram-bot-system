<?php
/**
 * Telegram 机器人核心类
 */

class TelegramBot {
    private $botToken;
    private $apiUrl;
    private $db;
    private $botId;
    private $config;

    public function __construct($botToken, $botId, $db, $config) {
        $this->botToken = $botToken;
        $this->botId = $botId;
        $this->db = $db;
        $this->config = $config;
        $this->apiUrl = $config['telegram']['api_url'];
    }

    /**
     * 设置 webhook
     */
    public function setWebhook($webhookUrl) {
        $url = $this->apiUrl . '/bot' . $this->botToken . '/setWebhook';
        
        return $this->makeRequest($url, [
            'url' => $webhookUrl
        ]);
    }

    /**
     * 发送消息
     */
    public function sendMessage($chatId, $text, $replyMarkup = null) {
        $url = $this->apiUrl . '/bot' . $this->botToken . '/sendMessage';
        
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        if ($replyMarkup) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->makeRequest($url, $params);
    }

    /**
     * 处理 webhook 更新
     */
    public function handleUpdate($update) {
        if (!isset($update['message'])) {
            return;
        }

        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $userId = $message['from']['id'];
        $userName = $message['from']['first_name'] ?? 'User';
        $text = $message['text'] ?? '';

        // 记录消息
        $this->logMessage($userId, $text, 'incoming');

        // 处理命令
        if (strpos($text, '/') === 0) {
            $this->handleCommand($chatId, $userId, $userName, $text);
        } else {
            $this->handleMessage($chatId, $userId, $text);
        }
    }

    /**
     * 处理命令
     */
    private function handleCommand($chatId, $userId, $userName, $text) {
        $parts = explode(' ', trim($text));
        $command = $parts[0];

        switch ($command) {
            case '/start':
                $this->handleStart($chatId, $userId, $userName);
                break;
            case '/buy':
                $this->handleBuy($chatId, $userId);
                break;
            case '/status':
                $this->handleStatus($chatId, $userId);
                break;
            case '/help':
                $this->handleHelp($chatId);
                break;
            default:
                $this->sendMessage($chatId, '❌ 未知命令，使用 /help 获取帮助');
        }
    }

    /**
     * /start 命令处理
     */
    private function handleStart($chatId, $userId, $userName) {
        $message = "👋 欢迎使用此机器人！\n\n";
        $message .= "🎯 功能介绍：\n";
        $message .= "• 💳 /buy - 购买订阅\n";
        $message .= "• 📊 /status - 查看订阅状态\n";
        $message .= "• ❓ /help - 获取帮助\n\n";
        $message .= "使用命令开始吧！";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '💳 购买订阅', 'callback_data' => 'buy_group'],
                    ['text' => '📊 订阅状态', 'callback_data' => 'check_status']
                ]
            ]
        ];

        $this->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * /buy 命令处理
     */
    private function handleBuy($chatId, $userId) {
        $result = $this->db->query(
            "SELECT ug.id, ug.group_name, ug.price, ug.duration_days 
             FROM user_groups ug 
             WHERE ug.bot_id = ? AND ug.status = 'active'",
            'i',
            [$this->botId]
        );

        if ($result->num_rows === 0) {
            $this->sendMessage($chatId, '暂无可购买的订阅套餐');
            return;
        }

        $message = "📦 选择要购买的套餐：\n\n";
        $keyboard = ['inline_keyboard' => []];

        while ($group = $result->fetch_assoc()) {
            $message .= "💎 <b>{$group['group_name']}</b>\n";
            $message .= "   价格：¥{$group['price']}\n";
            $message .= "   时长：{$group['duration_days']} 天\n\n";

            $keyboard['inline_keyboard'][] = [
                ['text' => $group['group_name'], 'callback_data' => 'purchase_' . $group['id']]
            ];
        }

        $this->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * /status 命令处理
     */
    private function handleStatus($chatId, $userId) {
        $result = $this->db->query(
            "SELECT us.*, ug.group_name, ug.price 
             FROM user_subscriptions us
             JOIN user_groups ug ON us.group_id = ug.id
             WHERE us.user_id = ? AND us.bot_id = ? AND us.status = 'active'",
            'ii',
            [$userId, $this->botId]
        );

        if ($result->num_rows === 0) {
            $this->sendMessage($chatId, '您还没有活跃的订阅，使用 /buy 购买订阅');
            return;
        }

        $message = "📊 您的订阅信息：\n\n";
        
        while ($subscription = $result->fetch_assoc()) {
            $expiryDate = strtotime($subscription['expiry_date']);
            $now = time();
            $daysLeft = ceil(($expiryDate - $now) / 86400);

            $message .= "💎 套餐：<b>{$subscription['group_name']}</b>\n";
            $message .= "💰 价格：¥{$subscription['price']}\n";
            $message .= "⏰ 剩余天数：<b>{$daysLeft}</b> 天\n";
            $message .= "📅 到期时间：" . date('Y-m-d H:i', $expiryDate) . "\n\n";
        }

        $this->sendMessage($chatId, $message);
    }

    /**
     * /help 命令处理
     */
    private function handleHelp($chatId) {
        $message = "❓ 帮助文档\n\n";
        $message .= "📝 可用命令：\n";
        $message .= "/start - 开始使用\n";
        $message .= "/buy - 购买订阅\n";
        $message .= "/status - 查看订阅状态\n";
        $message .= "/help - 显示此帮助信息\n\n";
        $message .= "💳 支付方式：易支付\n";
        $message .= "❓ 有问题？请联系管理员";

        $this->sendMessage($chatId, $message);
    }

    /**
     * 处理普通消息
     */
    private function handleMessage($chatId, $userId, $text) {
        $message = "收到您的消息：<code>" . htmlspecialchars($text) . "</code>\n\n";
        $message .= "请使用命令与我交互。使用 /help 获取帮助。";
        $this->sendMessage($chatId, $message);
    }

    /**
     * 记录消息
     */
    private function logMessage($userId, $text, $direction) {
        try {
            $this->db->execute(
                "INSERT INTO message_logs (bot_id, user_id, message_text, message_type, direction) 
                 VALUES (?, ?, ?, ?, ?)",
                'iisss',
                [$this->botId, $userId, $text, 'text', $direction]
            );
        } catch (Exception $e) {
            // 日志记录失败不中断流程
        }
    }

    /**
     * 发送 HTTP 请求
     */
    private function makeRequest($url, $params) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['telegram']['timeout']);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Telegram API 请求失败: " . $error);
        }

        return json_decode($response, true);
    }
}