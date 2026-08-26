-- Star Media Group consent site — MySQL 8 schema
CREATE DATABASE IF NOT EXISTS smg_consent
  CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE smg_consent;

CREATE TABLE IF NOT EXISTS consent_log (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  guid            CHAR(36)          NOT NULL UNIQUE,
  action          ENUM('accepted','declined') NOT NULL DEFAULT 'accepted',
  consent_version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  accepted_at     DATETIME          NOT NULL,
  expires_at      DATETIME          NOT NULL,
  ip_address      VARBINARY(16)     NULL COMMENT 'INET6_ATON()',
  user_agent      VARCHAR(255)      NULL,
  created_at      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_accepted_at (accepted_at),
  INDEX idx_version (consent_version),
  INDEX idx_action_time (action, accepted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bonus: admin portal
CREATE TABLE IF NOT EXISTS admin_users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(64)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL COMMENT 'password_hash(), PASSWORD_DEFAULT',
  last_login_at DATETIME     NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed an admin (replace the hash: php -r "echo password_hash('your-password', PASSWORD_DEFAULT);")
-- INSERT INTO admin_users (username, password_hash) VALUES ('admin', '$2y$...');

-- Admin login rate limiting (5 attempts / minute, keyed by IP)
CREATE TABLE IF NOT EXISTS login_attempts (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_address    VARBINARY(16) NOT NULL COMMENT 'INET6_ATON()',
  username      VARCHAR(64)   NULL,
  succeeded     TINYINT(1)    NOT NULL DEFAULT 0,
  attempted_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- About/Contact form submissions (persisted regardless of local mail delivery)
CREATE TABLE IF NOT EXISTS contact_messages (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name        VARCHAR(120)  NOT NULL,
  email            VARCHAR(190)  NOT NULL,
  subject          ENUM('advertising_partnerships','editorial_enquiry','data_privacy_request') NOT NULL,
  message          TEXT          NOT NULL,
  consent_privacy  TINYINT(1)    NOT NULL DEFAULT 0,
  ip_address       VARBINARY(16) NULL COMMENT 'INET6_ATON()',
  email_sent       TINYINT(1)    NOT NULL DEFAULT 0,
  created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin activity audit trail
CREATE TABLE IF NOT EXISTS admin_audit_log (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id      INT UNSIGNED  NULL COMMENT 'NULL if the actor could not be authenticated (e.g. failed login)',
  username      VARCHAR(64)   NULL,
  action        ENUM('login','logout','export','view_record','change_password') NOT NULL,
  detail        VARCHAR(255)  NULL COMMENT 'e.g. the filters used for an export, or the GUID viewed',
  ip_address    VARBINARY(16) NULL COMMENT 'INET6_ATON()',
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created_at (created_at),
  INDEX idx_admin_id (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
