ALTER TABLE personal_finance_categories
    ADD COLUMN icon VARCHAR(32) NOT NULL DEFAULT 'folder' AFTER color;

UPDATE personal_finance_categories SET icon = CASE code
    WHEN 'income_salary' THEN 'wallet'
    WHEN 'income_bonus' THEN 'sparkles'
    WHEN 'income_freelance' THEN 'briefcase'
    WHEN 'income_investment' THEN 'chart'
    WHEN 'expense_food' THEN 'coffee'
    WHEN 'expense_transport' THEN 'car'
    WHEN 'expense_housing' THEN 'home'
    WHEN 'expense_shopping' THEN 'bag'
    WHEN 'expense_utilities' THEN 'bolt'
    WHEN 'expense_health' THEN 'heart'
    WHEN 'expense_entertainment' THEN 'play'
    ELSE 'folder'
END;
