-- Migration v3 for existing installations (run once).
-- Adds email verification and Stripe payment tracking.
--
-- Requires MySQL 8.0.29+ or MariaDB 10.0.2+ for "ADD COLUMN IF NOT EXISTS".
-- If your server is older, remove "IF NOT EXISTS" from each line below and
-- just ignore any "Duplicate column name" errors on re-run.
--
-- Usage:
--   mysql -u root -p food-ordering-system < data/migration_v3.sql

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS Email_Verified            TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS Verification_Token         VARCHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS Verification_Token_Expiry  DATETIME NULL;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS Stripe_Session_ID VARCHAR(255) NULL;

-- Existing accounts predate email verification — treat them as already
-- verified so nobody who could already log in gets locked out of anything
-- that later checks this flag.
UPDATE users SET Email_Verified = 1 WHERE Email_Verified = 0;
