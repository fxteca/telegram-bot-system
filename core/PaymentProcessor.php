<?php
/**
 * 易支付处理类
 */

class PaymentProcessor {
    private $partnerId;
    private $partnerKey;
    private $db;
    private $config;

    public function __construct($db, $config) {
        $this->db = $db;
        $this->config = $config;
        $this->partnerId = $config['yifu_pay']['partner_id'];
        $this->partnerKey = $config['yifu_pay']['partner_key'];
    }

    /**
     * 创建支付订单
     */
    public function createOrder($userId, $botId, $groupId, $amount) {
        $outTradeNo = 'ORDER_' . $userId . '_' . time() . '_' . rand(1000, 9999);

        // 记录支付
        $result = $this->db->execute(
            "INSERT INTO payments (user_id, bot_id, group_id, amount, payment_method, out_trade_no, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            'iiidsss',
            [$userId, $botId, $groupId, $amount, 'yifu_pay', $outTradeNo, 'pending']
        );

        if (!$result['success']) {
            throw new Exception("创建支付记录失败");
        }

        return $this->generatePaymentUrl($outTradeNo, $amount, $userId);
    }

    /**
     * 生成易支付 URL
     */
    private function generatePaymentUrl($outTradeNo, $amount, $userId) {
        $params = [
            'partner' => $this->partnerId,
            'out_trade_no' => $outTradeNo,
            'name' => '机器人订阅费用',
            'money' => number_format($amount, 2, '.', ''),
            'type' => 'alipay',
            'notify_url' => $this->config['yifu_pay']['notify_url'],
            'return_url' => $this->config['yifu_pay']['return_url'],
            'sitename' => '机器人订阅系统'
        ];

        ksort($params);
        $signStr = '';
        foreach ($params as $k => $v) {
            $signStr .= $k . '=' . $v . '&';
        }
        $signStr .= 'key=' . $this->partnerKey;
        
        $sign = md5($signStr);
        $params['sign'] = $sign;
        $params['sign_type'] = 'MD5';

        return 'https://api.yifubao.com.cn/submit/?' . http_build_query($params);
    }

    /**
     * 验证回调签名
     */
    public function verifyNotify($data) {
        $sign = $data['sign'] ?? '';
        unset($data['sign']);
        unset($data['sign_type']);

        ksort($data);
        $signStr = '';
        foreach ($data as $k => $v) {
            $signStr .= $k . '=' . $v . '&';
        }
        $signStr .= 'key=' . $this->partnerKey;
        
        $calcSign = md5($signStr);

        return $sign === $calcSign;
    }

    /**
     * 处理支付回调
     */
    public function handleNotify($data) {
        if (!$this->verifyNotify($data)) {
            return false;
        }

        $outTradeNo = $data['out_trade_no'] ?? '';
        $payId = $data['payid'] ?? '';
        $status = $data['status'] ?? '';

        // 查询支付记录
        $result = $this->db->query(
            "SELECT * FROM payments WHERE out_trade_no = ?",
            's',
            [$outTradeNo]
        );

        if ($result->num_rows === 0) {
            return false;
        }

        $payment = $result->fetch_assoc();

        if ($status === 'success' && $payment['status'] === 'pending') {
            // 更新支付状态
            $this->db->execute(
                "UPDATE payments SET status = ?, pay_id = ? WHERE id = ?",
                'ssi',
                ['completed', $payId, $payment['id']]
            );

            // 创建订阅
            $this->createSubscription($payment);
            return true;
        }

        return false;
    }

    /**
     * 创建订阅
     */
    private function createSubscription($payment) {
        $groupResult = $this->db->query(
            "SELECT duration_days FROM user_groups WHERE id = ?",
            'i',
            [$payment['group_id']]
        );

        if ($groupResult->num_rows === 0) {
            return false;
        }

        $group = $groupResult->fetch_assoc();
        $expiryDate = date('Y-m-d H:i:s', strtotime('+' . $group['duration_days'] . ' days'));

        // 检查是否存在订阅
        $checkResult = $this->db->query(
            "SELECT id FROM user_subscriptions WHERE user_id = ? AND bot_id = ? AND group_id = ? AND status = 'active'",
            'iii',
            [$payment['user_id'], $payment['bot_id'], $payment['group_id']]
        );

        if ($checkResult->num_rows > 0) {
            // 延长现有订阅
            return $this->db->execute(
                "UPDATE user_subscriptions SET expiry_date = ?, payment_id = ?, updated_at = NOW() 
                 WHERE user_id = ? AND bot_id = ? AND group_id = ? AND status = 'active'",
                'ssiii',
                [$expiryDate, $payment['id'], $payment['user_id'], $payment['bot_id'], $payment['group_id']]
            );
        } else {
            // 创建新订阅
            return $this->db->execute(
                "INSERT INTO user_subscriptions (user_id, bot_id, group_id, expiry_date, payment_id, status)
                 VALUES (?, ?, ?, ?, ?, ?)",
                'iiisss',
                [$payment['user_id'], $payment['bot_id'], $payment['group_id'], $expiryDate, $payment['id'], 'active']
            );
        }
    }
}