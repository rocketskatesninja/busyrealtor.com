-- =============================================================================
-- BusyRealtor.com — Database Setup Script
-- Run BEFORE the Laravel migrations, as root or a MySQL admin user:
--
--   mysql -u root -p < setup_db.sql
--
-- This creates the database and application user only.
-- All tables are created by: php artisan migrate
-- Demo data is loaded by:    php artisan db:seed
--
-- To customise, edit the three variables below before running.
-- =============================================================================

-- ── Variables (edit these) ────────────────────────────────────────────────────
SET @db_name   = 'busyrealtor';
SET @db_user   = 'busyrealtor_user';
SET @db_pass   = 'CHANGE_ME_STRONG_PASSWORD';

-- ── Create database ───────────────────────────────────────────────────────────
CREATE DATABASE IF NOT EXISTS `busyrealtor`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- ── Create user (local connections only) ─────────────────────────────────────
-- NOTE: MySQL doesn't support variables in CREATE USER / GRANT,
--       so replace the values below manually if you changed them above.
CREATE USER IF NOT EXISTS 'busyrealtor_user'@'localhost'
    IDENTIFIED BY 'CHANGE_ME_STRONG_PASSWORD';

-- ── Grant privileges ──────────────────────────────────────────────────────────
GRANT ALL PRIVILEGES ON `busyrealtor`.* TO 'busyrealtor_user'@'localhost';

-- ── Flush ─────────────────────────────────────────────────────────────────────
FLUSH PRIVILEGES;

-- ── Verify ───────────────────────────────────────────────────────────────────
SELECT
    SCHEMA_NAME       AS `database`,
    DEFAULT_CHARACTER_SET_NAME AS `charset`,
    DEFAULT_COLLATION_NAME     AS `collation`
FROM information_schema.SCHEMATA
WHERE SCHEMA_NAME = 'busyrealtor';

SELECT
    User        AS `user`,
    Host        AS `host`,
    plugin      AS `auth_plugin`
FROM mysql.user
WHERE User = 'busyrealtor_user';

-- =============================================================================
-- After running this file, continue in the app directory:
--
--   cp .env.example .env
--   # Edit .env with the DB credentials above
--   php artisan key:generate
--   php artisan migrate --force
--   php artisan db:seed --force
-- =============================================================================
