# Telegram 机器人系统部署指南

## 环境要求
- PHP 7.2+
- MySQL 5.7+
- HTTPS（用于 Webhook）
- curl 扩展

## 安装步骤

### 1. 创建数据库
```bash
mysql -u root -p
CREATE DATABASE telegram_bot_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE telegram_bot_system;
source database.sql;
```

### 2. 项目部署
```bash
cd /var/www/html
git clone https://github.com/fxteca/telegram-bot-system.git
cd telegram-bot-system

# 设置权限
chmod 755 api/
chmod 644 config/config.php
chmod 644 admin/index.html
```

### 3. 配置文件设置
编辑 `config/config.php`，填入你的信息：

```php
'db' => [
    'host' => 'localhost',
    'user' => 'root',
    'password' => 'your_db_password',
    'database' => 'telegram_bot_system',
    'charset' => 'utf8mb4'
],
'yifu_pay' => [
    'partner_id' => 'your_partner_id',
    'partner_key' => 'your_partner_key',
    'notify_url' => 'https://your-domain.com/api/payment/notify.php',
    'return_url' => 'https://your-domain.com/api/payment/return.php'
],
'admin' => [
    'username' => 'admin',
    'password_hash' => password_hash('your_password', PASSWORD_BCRYPT)
]
```

### 4. Web 服务器配置

#### Nginx 配置
```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/html/telegram-bot-system;
    index index.html;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

#### Apache 配置
```apache
<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /var/www/html/telegram-bot-system
    
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem
    
    <Directory /var/www/html/telegram-bot-system>
        AllowOverride All
        Require all granted
    </Directory>
    
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/var/run/php-fpm.sock|fcgi://localhost/"
    </FilesMatch>
</VirtualHost>
```

### 5. 设置 Webhook

对于每个机器人，使用以下命令设置 webhook：

```bash
# 先在数据库中添加机器人，假设 bot_id = 1, bot_token = YOUR_TOKEN
curl -X POST https://api.telegram.org/botYOUR_TOKEN/setWebhook \
  -H 'Content-Type: application/json' \
  -d '{"url": "https://your-domain.com/api/webhook.php?bot_token=YOUR_TOKEN&bot_id=1"}'
```

验证 webhook 设置：
```bash
curl -X GET https://api.telegram.org/botYOUR_TOKEN/getWebhookInfo
```

### 6. 访问管理界面

打开浏览器访问：
```
https://your-domain.com/admin/index.html
```

默认账号密码为 `admin` / `admin123`（请及时修改）

## 首次使用步骤

1. **登录管理界面**
   - 输入默认用户名和密码
   - 修改密码（可选但推荐）

2. **添加机器人**
   - 点击 "机器人管理"
   - 点击 "➕ 添加机器人"
   - 填入以下信息：
     - 机器人名称：如 "推广机器人"
     - Bot Token：从 @BotFather 获取
     - Bot ID：机器人的数字 ID
   - 点击保存

3. **创建用户组**
   - 点击 "用户组管理"
   - 选择刚添加的机器人
   - 点击 "➕ 添加用户组"
   - 填入信息：
     - 组名称：如 "VIP会员"
     - 价格：如 9.99
     - 时长：30天
     - 描述：可选
   - 点击保存

4. **配置易支付**
   - 登录易支付后台
   - 获取 Partner ID 和 Partner Key
   - 填入 `config/config.php`

5. **测试**
   - 在 Telegram 中找到你的机器人
   - 发送 `/start` 命令
   - 点击 "购买订阅" 测试支付流程

## 常见问题

### Webhook 无法连接
- 检查 HTTPS 证书有效性
- 确认防火墙开放 443 端口
- 验证 URL 路径正确
- 查看服务器日志

### 支付失败
- 检查易支付配置
- 验证签名计算正确
- 查看支付日志表
- 检查服务器时间同步

### 数据库连接失败
- 确认数据库服务运行
- 验证用户名和密码
- 检查防火墙设置
- 查看 MySQL 错误日志

### PHP 错误
- 检查 PHP 版本 >= 7.2
- 启用 curl 扩展
- 启用 session 功能
- 检查错误日志

## 性能优化

1. **启用 OpCache**
```php
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
```

2. **数据库优化**
```sql
CREATE INDEX idx_user_sub ON user_subscriptions(user_id, bot_id);
CREATE INDEX idx_payment ON payments(out_trade_no, status);
CREATE INDEX idx_log ON message_logs(bot_id, created_at);
```

3. **使用 Redis 缓存**（可选）
```php
// 缓存机器人配置和用户组
```

## 监控和维护

### 日志监控
```bash
# 查看 PHP 错误日志
tail -f /var/log/php-fpm.log

# 查看 Nginx 日志
tail -f /var/log/nginx/error.log

# 查看 MySQL 日志
tail -f /var/log/mysql/error.log
```

### 数据库备份
```bash
# 每日备份
mysqldump -u root -p telegram_bot_system > backup_$(date +%Y%m%d).sql
```

### 定期清理
```sql
-- 清理 30 天前的日志
DELETE FROM message_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

## 安全建议

1. ✅ 修改默认管理员密码
2. ✅ 定期备份数据库
3. ✅ 启用 HTTPS
4. ✅ 限制 API 访问
5. ✅ 监控日志文件
6. ✅ 更新依赖包
7. ✅ 设置文件权限
8. ✅ 启用防火墙

## 技术支持

遇到问题？
- 查看文档
- 提交 Issue
- 联系作者