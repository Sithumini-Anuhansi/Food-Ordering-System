-- Migration v4 for existing installations (run once).
-- Adds delivery-claiming, kitchen prep-time ETA, and customer-review
-- moderation — the workflow features that make each role behave like a
-- real food-delivery platform.
--
-- Requires MySQL 8.0.29+ or MariaDB 10.0.2+ for "ADD COLUMN IF NOT EXISTS".
-- If your server is older, remove "IF NOT EXISTS" and ignore any
-- "Duplicate column name" errors on re-run.
--
-- Usage:
--   mysql -u root -p food-ordering-system < data/migration_v4.sql

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS Assigned_Driver_ID   INT NULL,
    ADD COLUMN IF NOT EXISTS Estimated_Ready_Time  DATETIME NULL;

-- Only add the FK if it doesn't already exist (MySQL has no "ADD CONSTRAINT
-- IF NOT EXISTS", so this is wrapped defensively — safe to ignore an error
-- here on re-run).
ALTER TABLE orders
    ADD CONSTRAINT fk_orders_driver FOREIGN KEY (Assigned_Driver_ID) REFERENCES users(User_ID);

ALTER TABLE reviews
    ADD COLUMN IF NOT EXISTS Order_ID INT NULL,
    ADD COLUMN IF NOT EXISTS User_ID  INT NULL,
    ADD COLUMN IF NOT EXISTS Approved TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE reviews
    ADD CONSTRAINT fk_reviews_order FOREIGN KEY (Order_ID) REFERENCES orders(ID);
ALTER TABLE reviews
    ADD CONSTRAINT fk_reviews_user FOREIGN KEY (User_ID) REFERENCES users(User_ID);

-- Existing seeded/admin-added reviews should stay visible.
UPDATE reviews SET Approved = 1 WHERE Order_ID IS NULL;
