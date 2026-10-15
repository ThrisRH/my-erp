CREATE TABLE personal_finance_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NULL,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(100) NOT NULL,
    transaction_type ENUM('income', 'expense') NOT NULL,
    color CHAR(7) NOT NULL DEFAULT '#6C757D',
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_personal_finance_category_code UNIQUE (code),
    CONSTRAINT fk_personal_finance_categories_employee
        FOREIGN KEY (employee_id) REFERENCES employees (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE personal_finance_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    transaction_type ENUM('income', 'expense') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    occurred_on DATE NOT NULL,
    notes VARCHAR(1000) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_personal_finance_transaction_amount CHECK (amount > 0),
    CONSTRAINT fk_personal_finance_transactions_employee
        FOREIGN KEY (employee_id) REFERENCES employees (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_personal_finance_transactions_category
        FOREIGN KEY (category_id) REFERENCES personal_finance_categories (id)
        ON DELETE RESTRICT,
    INDEX idx_personal_finance_transactions_employee_date (employee_id, occurred_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO employees (employee_code, first_name, last_name, work_email, job_title, employment_status)
VALUES ('PFIN-DEMO-001', 'Demo', 'Employee', 'demo.employee@my-erp.local', 'Demo employee', 'active')
ON DUPLICATE KEY UPDATE employee_code = VALUES(employee_code);

INSERT INTO personal_finance_categories (code, name, transaction_type, color, is_system) VALUES
    ('income_salary', 'Salary', 'income', '#198754', 1),
    ('income_bonus', 'Bonus', 'income', '#0D6EFD', 1),
    ('income_freelance', 'Freelance', 'income', '#6F42C1', 1),
    ('income_investment', 'Investment', 'income', '#0DCAF0', 1),
    ('expense_food', 'Food & drinks', 'expense', '#DC3545', 1),
    ('expense_transport', 'Transport', 'expense', '#FD7E14', 1),
    ('expense_housing', 'Housing', 'expense', '#6C757D', 1),
    ('expense_shopping', 'Shopping', 'expense', '#D63384', 1),
    ('expense_utilities', 'Utilities', 'expense', '#20C997', 1),
    ('expense_health', 'Health', 'expense', '#198754', 1),
    ('expense_entertainment', 'Entertainment', 'expense', '#6610F2', 1);
