CREATE TABLE personal_finance_wallets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    wallet_type ENUM('cash', 'bank', 'e_wallet', 'other') NOT NULL DEFAULT 'other',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_personal_finance_wallet_employee_name UNIQUE (employee_id, name),
    CONSTRAINT fk_personal_finance_wallets_employee
        FOREIGN KEY (employee_id) REFERENCES employees (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO personal_finance_wallets (employee_id, name, wallet_type)
SELECT employee.id, 'Tiền mặt', 'cash'
FROM employees AS employee
WHERE employee.employment_status = 'active';

ALTER TABLE personal_finance_transactions
    ADD COLUMN wallet_id BIGINT UNSIGNED NULL AFTER employee_id,
    ADD INDEX idx_personal_finance_transactions_wallet (wallet_id),
    ADD CONSTRAINT fk_personal_finance_transactions_wallet
        FOREIGN KEY (wallet_id) REFERENCES personal_finance_wallets (id) ON DELETE RESTRICT;

UPDATE personal_finance_transactions AS transaction
INNER JOIN personal_finance_wallets AS wallet
        ON wallet.employee_id = transaction.employee_id AND wallet.name = 'Tiền mặt'
SET transaction.wallet_id = wallet.id
WHERE transaction.wallet_id IS NULL;

ALTER TABLE personal_finance_transactions
    MODIFY wallet_id BIGINT UNSIGNED NOT NULL;
