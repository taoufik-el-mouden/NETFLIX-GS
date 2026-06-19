<?php
require 'config/db.php';
$pdo = get_pdo();
$pdo->exec("CREATE TABLE IF NOT EXISTS `expenses` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category`     ENUM('ads','other') NOT NULL DEFAULT 'ads',
    `description`  VARCHAR(500) NOT NULL,
    `amount`       DECIMAL(10,2) NOT NULL,
    `expense_date` DATE NOT NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
echo "DB Updated";
