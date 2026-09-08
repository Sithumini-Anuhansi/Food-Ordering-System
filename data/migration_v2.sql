-- Migration for existing installations (run once).
-- Adds everything needed for: checkout details (delivery address/time,
-- payment method, fee/tax), inventory tracking, forgot-password, and
-- login rate limiting — without touching any existing data.
--
-- Requires MySQL 8.0.29+ or MariaDB 10.0.2+ for "ADD COLUMN IF NOT EXISTS".
-- If your server is older than that, remove "IF NOT EXISTS" from each line
-- below and just ignore any "Duplicate column name" errors on re-run.
--
-- Usage:
--   mysql -u root -p food-ordering-system < data/migration_v2.sql

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS Reset_Token           VARCHAR(64)  NULL,
    ADD COLUMN IF NOT EXISTS Reset_Token_Expiry     DATETIME     NULL,
    ADD COLUMN IF NOT EXISTS Failed_Login_Attempts  INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS Lockout_Until          DATETIME     NULL;

ALTER TABLE food_items
    ADD COLUMN IF NOT EXISTS Stock_Quantity INT NULL;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS Delivery_Fee         DECIMAL(10,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS Tax                  DECIMAL(10,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS Delivery_Address     VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS Delivery_Time        VARCHAR(50)  NULL,
    ADD COLUMN IF NOT EXISTS Special_Instructions TEXT NULL,
    ADD COLUMN IF NOT EXISTS Payment_Method       VARCHAR(30) NOT NULL DEFAULT 'Cash on Delivery',
    ADD COLUMN IF NOT EXISTS Payment_Status       VARCHAR(20) NOT NULL DEFAULT 'Unpaid';
