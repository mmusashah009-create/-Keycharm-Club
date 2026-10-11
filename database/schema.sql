CREATE DATABASE IF NOT EXISTS noir_form
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE noir_form;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(160) NOT NULL,
    category VARCHAR(80) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    badge VARCHAR(40) NOT NULL DEFAULT '',
    image VARCHAR(120) NOT NULL,
    description VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_products_catalog (is_active, sort_order, id)
) ENGINE=InnoDB;

INSERT INTO products (name, category, price, badge, image, description, sort_order)
SELECT seed.name, seed.category, seed.price, seed.badge, seed.image, seed.description, seed.sort_order
FROM (
    SELECT 'Sculpted Wool Coat' AS name, 'Outerwear' AS category, 285.00 AS price, 'BESTSELLER' AS badge, 'photo-1539109136881-3be0616acf4b' AS image, 'A quietly dramatic silhouette in double-faced Italian wool.' AS description, 1 AS sort_order
    UNION ALL SELECT 'The Everyday Knit', 'Knitwear', 148.00, 'NEW ARRIVAL', 'photo-1576566588028-4147f3842f27', 'Soft, weighty organic cotton with a perfectly relaxed fit.', 2
    UNION ALL SELECT 'Forma Leather Tote', 'Accessories', 220.00, '', 'photo-1590874103328-eac38a683ce7', 'A considered carryall, cut from supple responsibly sourced leather.', 3
    UNION ALL SELECT 'Sunday Tailored Trouser', 'Clothing', 175.00, 'LOW STOCK', 'photo-1509631179647-0177331693ae', 'Easy movement, thoughtful tailoring, and a little extra length.', 4
    UNION ALL SELECT 'No. 04 Ribbed Tank', 'Essentials', 68.00, '', 'photo-1503342217505-b0a15ec3261c', 'An essential layer made to be worn on repeat.', 5
    UNION ALL SELECT 'Soft Structure Blazer', 'Outerwear', 248.00, 'JUST IN', 'photo-1591047139829-d91aecb6caea', 'Unlined and endlessly versatile. The blazer, rethought.', 6
) AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM products existing WHERE existing.name = seed.name
);

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_name VARCHAR(160) NOT NULL,
    customer_email VARCHAR(254) NOT NULL,
    customer_phone VARCHAR(40) NOT NULL,
    address_line1 VARCHAR(200) NOT NULL,
    address_line2 VARCHAR(200) NOT NULL DEFAULT '',
    city VARCHAR(100) NOT NULL,
    region VARCHAR(100) NOT NULL,
    postal_code VARCHAR(30) NOT NULL,
    country VARCHAR(100) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'usd',
    subtotal DECIMAL(10, 2) NOT NULL,
    shipping DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    checkout_token CHAR(64) DEFAULT NULL,
    payment_method VARCHAR(30) NOT NULL DEFAULT 'cash_on_delivery',
    payment_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    order_status VARCHAR(30) NOT NULL DEFAULT 'new',
    refunded_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_checkout_token (checkout_token),
    INDEX idx_orders_created (created_at),
    INDEX idx_orders_status (payment_status, order_status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED DEFAULT NULL,
    product_name VARCHAR(160) NOT NULL,
    product_image VARCHAR(120) NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_total DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_order_items_order (order_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
) ENGINE=InnoDB;
