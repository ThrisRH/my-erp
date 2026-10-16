ALTER TABLE accounts
    ADD COLUMN email_verified_at DATETIME NULL,
    ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 0;

UPDATE accounts SET email_verified_at = NOW() WHERE is_admin = 1;

CREATE TABLE account_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    purpose ENUM('verify_email', 'reset_password') NOT NULL,
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_account_tokens_hash UNIQUE (token_hash),
    KEY idx_account_tokens_account_purpose (account_id, purpose),
    CONSTRAINT fk_account_tokens_account FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
