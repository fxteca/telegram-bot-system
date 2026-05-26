# 🤖 Telegram 双向机器人系统

一个完整的 PHP Telegram 双向机器人系统，支持多机器人管理、用户订阅、易支付集成等功能。

## 功能特性

✨ **核心功能**
- 🤖 多机器人管理和配置
- 👥 灵活的用户组系统
- 💳 易支付支付集成
- 📅 自定义订阅时长和费用
- 📊 完整的订阅和支付管理
- 💬 双向消息通信
- 📝 完整的操作日志

## 快速开始

### 环境要求
- PHP 7.2+
- MySQL 5.7+
- HTTPS 支持（用于 Webhook）
- curl 扩展

### 安装步骤

1. **克隆项目**
```bash
git clone https://github.com/fxteca/telegram-bot-system.git
cd telegram-bot-system
```

2. **创建数据库**
```bash
mysql -u root -p < database.sql
```

3. **配置文件**
编辑 `config/config.php`，填入你的信息：
```php
'db' => [
    'host' => 'localhost',
    'user' => 'root',
    'password' => 'your_password',
    'database' => 'telegram_bot_system'
],
'yifu_pay' => [
    'partner_id' => 'your_partner_id',
    'partner_key' => 'your_partner_key'
]
```

4. **设置 Webhook**
对每个机器人，使用 @BotFather 或 API 设置：
```
https://your-domain.com/api/webhook.php?bot_token=YOUR_TOKEN&bot_id=1
```

5. **访问管理界面**
```
https://your-domain.com/admin/index.html
```

## 项目结构

```
telegram-bot-system/
├── config/
│   └── config.php              # 配置文件
├── core/
│   ├── Database.php            # 数据库连接
│   ├── TelegramBot.php         # Bot 核心类
│   └── PaymentProcessor.php    # 支付处理
├── api/
│   ├── webhook.php             # Webhook 入口
│   ├── payment/
│   │   └── notify.php          # 支付回调处理
│   └── admin/
│       ├── bots.php            # 机器人 API
│       ├── groups.php          # 用户组 API
│       ├── subscriptions.php    # 订阅 API
│       └── payments.php        # 支付记录 API
├── admin/
│   ├── index.html              # 管理界面
│   ├── css/
│   │   └── style.css           # 样式文件
│   └── js/
│       └── main.js             # 管理脚本
├── database.sql                # 数据库结构
└── README.md                   # 说明文档
```

## 使用指南

### 管理员操作

1. **添加机器人**
   - 访问管理界面
   - 点击 "添加机器人"
   - 输入机器人名称、Token 和 ID

2. **创建用户组**
   - 选择机器人
   - 设置组名称、价格和时长
   - 保存配置

3. **查看订阅和支付**
   - 实时查看用户订阅情况
   - 监控支付状态

### 用户操作

1. 与机器人聊天
2. 使用 `/buy` 命令或点击购买按钮
3. 选择套餐并支付
4. 支付成功后自动激活订阅

## API 接口

### 机器人管理
- `GET /api/admin/bots.php?action=list` - 获取机器人列表
- `POST /api/admin/bots.php?action=add` - 添加机器人
- `POST /api/admin/bots.php?action=update` - 更新机器人
- `POST /api/admin/bots.php?action=delete` - 删除机器人

### 用户组管理
- `GET /api/admin/groups.php?action=list&bot_id=ID` - 获取用户组
- `POST /api/admin/groups.php?action=add` - 添加用户组
- `POST /api/admin/groups.php?action=update` - 更新用户组

### 订阅管理
- `GET /api/admin/subscriptions.php?action=list` - 获取订阅列表
- `POST /api/admin/subscriptions.php?action=extend` - 延长订阅

## 数据库表说明

| 表名 | 说明 |
|------|------|
| `bots` | 机器人配置 |
| `user_groups` | 用户组定义 |
| `user_subscriptions` | 用户订阅记录 |
| `payments` | 支付记录 |
| `message_logs` | 消息日志 |

## 安全建议

1. 修改默认管理员密码
2. 使用 HTTPS 连接
3. 定期备份数据库
4. 限制 API 访问
5. 监控异常支付

## 故障排除

### Webhook 无法连接
- 检查 HTTPS 证书有效性
- 确认防火墙开放 443 端口
- 验证 URL 路径正确

### 支付失败
- 检查易支付配置
- 验证签名计算正确
- 查看支付日志

## 贡献

欢迎提交 Issue 和 Pull Request！

## 许可证

MIT License

## 联系方式

如有问题，请提交 Issue 或联系作者。