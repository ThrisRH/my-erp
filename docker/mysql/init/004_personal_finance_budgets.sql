CREATE TABLE personal_finance_budgets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    budget_month DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_personal_finance_budget_month UNIQUE (employee_id, budget_month),
    CONSTRAINT fk_personal_finance_budgets_employee
        FOREIGN KEY (employee_id) REFERENCES employees (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE personal_finance_budget_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    budget_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_personal_finance_budget_category UNIQUE (budget_id, category_id),
    CONSTRAINT chk_personal_finance_budget_amount CHECK (amount >= 0),
    CONSTRAINT fk_personal_finance_budget_items_budget
        FOREIGN KEY (budget_id) REFERENCES personal_finance_budgets (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_personal_finance_budget_items_category
        FOREIGN KEY (category_id) REFERENCES personal_finance_categories (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
