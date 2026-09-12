-- Bookhaven database setup

CREATE DATABASE IF NOT EXISTS bookhaven
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bookhaven;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_number VARCHAR(32) NOT NULL,
    user_id INT UNSIGNED NULL,

    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    delivery_address VARCHAR(200) NOT NULL,
    city VARCHAR(80) NOT NULL,
    postal_code VARCHAR(12) NOT NULL,

    payment_method VARCHAR(40) NOT NULL,
    checkout_source VARCHAR(20) NOT NULL,
    order_items LONGTEXT NOT NULL,
    item_count INT UNSIGNED NOT NULL,

    subtotal DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,

    order_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY unique_order_number (order_number),
    KEY index_customer_email (customer_email),
    KEY index_order_date (created_at)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

SHOW TABLES;