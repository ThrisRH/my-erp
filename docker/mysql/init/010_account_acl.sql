ALTER TABLE accounts
    ADD COLUMN permissions JSON NULL,
    ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN admin_slot TINYINT GENERATED ALWAYS AS (IF(is_admin = 1, 1, NULL)) STORED,
    ADD CONSTRAINT uq_accounts_single_admin UNIQUE (admin_slot);
