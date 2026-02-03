-- Admin system schema (Hostinger/MySQL)

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(80) NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock_quantity INT NOT NULL DEFAULT 0,
  low_stock_threshold INT NOT NULL DEFAULT 5,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS waiters (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  pin_hash VARCHAR(255) NULL,
  role ENUM('waiter','manager') NOT NULL DEFAULT 'waiter',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB;

CREATE INDEX idx_waiters_pin ON waiters (pin_hash(10));

CREATE TABLE IF NOT EXISTS tables (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  table_name VARCHAR(10) NOT NULL UNIQUE,
  assigned_waiter_id INT UNSIGNED NULL,
  status ENUM('available','occupied','billing') NOT NULL DEFAULT 'available',
  updated_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_tables_waiter FOREIGN KEY (assigned_waiter_id) REFERENCES waiters(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  waiter_id INT UNSIGNED NOT NULL,
  table_id INT UNSIGNED NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_method ENUM('cash','transfer','card') NOT NULL DEFAULT 'cash',
  sale_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sales_waiter FOREIGN KEY (waiter_id) REFERENCES waiters(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_sales_table FOREIGN KEY (table_id) REFERENCES tables(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_sales_date (sale_date),
  INDEX idx_sales_waiter (waiter_id),
  INDEX idx_sales_table (table_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sale_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sale_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  is_voided TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  UNIQUE KEY uniq_sale_product (sale_id, product_id),
  INDEX idx_sale_items_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS void_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sale_item_id INT UNSIGNED NOT NULL,
  voided_by INT UNSIGNED NOT NULL,
  reason VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_void_logs_item FOREIGN KEY (sale_item_id) REFERENCES sale_items(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_void_logs_waiter FOREIGN KEY (voided_by) REFERENCES waiters(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_void_logs_created_at (created_at)
) ENGINE=InnoDB;

-- Seed tables T1-T12
INSERT INTO tables (table_name, status)
SELECT * FROM (
  SELECT 'T1' AS table_name, 'available' AS status UNION ALL
  SELECT 'T2', 'available' UNION ALL
  SELECT 'T3', 'available' UNION ALL
  SELECT 'T4', 'available' UNION ALL
  SELECT 'T5', 'available' UNION ALL
  SELECT 'T6', 'available' UNION ALL
  SELECT 'T7', 'available' UNION ALL
  SELECT 'T8', 'available' UNION ALL
  SELECT 'T9', 'available' UNION ALL
  SELECT 'T10', 'available' UNION ALL
  SELECT 'T11', 'available' UNION ALL
  SELECT 'T12', 'available'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM tables WHERE table_name = seed.table_name);

-- If waiters table already exists, run:
-- ALTER TABLE waiters ADD COLUMN pin_hash VARCHAR(255) NULL;
-- CREATE INDEX idx_waiters_pin ON waiters (pin_hash(10));

-- If sales table already exists, run:
-- ALTER TABLE sales ADD COLUMN payment_method ENUM('cash','transfer','card') NOT NULL DEFAULT 'cash';
-- ALTER TABLE sale_items ADD COLUMN is_voided TINYINT(1) NOT NULL DEFAULT 0;
-- CREATE TABLE void_logs (...)
