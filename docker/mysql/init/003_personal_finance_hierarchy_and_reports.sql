ALTER TABLE personal_finance_categories
    ADD COLUMN parent_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER employee_id,
    ADD INDEX idx_personal_finance_categories_parent (parent_id);

ALTER TABLE personal_finance_transactions
    ADD COLUMN occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER occurred_on,
    ADD INDEX idx_personal_finance_transactions_employee_occurred_at (employee_id, occurred_at);

UPDATE personal_finance_transactions
SET occurred_at = CONCAT(occurred_on, ' 00:00:00')
WHERE occurred_at IS NULL OR TIME(occurred_at) = '00:00:00';
