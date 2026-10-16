USE noir_form;

CREATE TABLE IF NOT EXISTS product_variants (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    color_name VARCHAR(50) NOT NULL,
    color_hex CHAR(7) NOT NULL,
    image VARCHAR(120) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    INDEX idx_product_variants_product (product_id, sort_order, id),
    CONSTRAINT fk_product_variants_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE order_items
    ADD COLUMN color_name VARCHAR(50) NOT NULL DEFAULT '' AFTER product_image;
