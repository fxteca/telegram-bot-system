-- 机器人配置表
CREATE TABLE IF NOT EXISTS `bots` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `bot_name` VARCHAR(255) NOT NULL UNIQUE,
  `bot_token` VARCHAR(255) NOT NULL UNIQUE,
  `bot_id` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 用户组表
CREATE TABLE IF NOT EXISTS `user_groups` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `bot_id` INT NOT NULL,
  `group_name` VARCHAR(255) NOT NULL,
  `group_description` TEXT,
  `price` DECIMAL(10, 2) NOT NULL,
  `duration_days` INT NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`bot_id`) REFERENCES `bots`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_bot_group` (`bot_id`, `group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 用户订阅表
CREATE TABLE IF NOT EXISTS `user_subscriptions` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` BIGINT NOT NULL,
  `user_name` VARCHAR(255),
  `bot_id` INT NOT NULL,
  `group_id` INT NOT NULL,
  `start_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expiry_date` DATETIME NOT NULL,
  `status` ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
  `payment_id` VARCHAR(255) UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`bot_id`) REFERENCES `bots`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`group_id`) REFERENCES `user_groups`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`, `bot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 支付记录表
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` BIGINT NOT NULL,
  `bot_id` INT NOT NULL,
  `group_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `out_trade_no` VARCHAR(255) UNIQUE NOT NULL,
  `pay_id` VARCHAR(255),
  `status` ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`bot_id`) REFERENCES `bots`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`group_id`) REFERENCES `user_groups`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 消息日志表
CREATE TABLE IF NOT EXISTS `message_logs` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `bot_id` INT NOT NULL,
  `user_id` BIGINT NOT NULL,
  `message_text` TEXT,
  `message_type` VARCHAR(50),
  `direction` ENUM('incoming', 'outgoing') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`bot_id`) REFERENCES `bots`(`id`) ON DELETE CASCADE,
  INDEX `idx_bot_user` (`bot_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;