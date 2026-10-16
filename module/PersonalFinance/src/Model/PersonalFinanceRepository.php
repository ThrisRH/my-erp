<?php

declare(strict_types=1);

namespace PersonalFinance\Model;

use DateTimeImmutable;
use PDO;

class PersonalFinanceRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /** @return array<string, mixed>|null */
    public function findCurrentEmployee(int $employeeId): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT id, employee_code, first_name, last_name, work_email
             FROM employees
             WHERE employment_status = 'active' AND id = :employee_id
             LIMIT 1"
        );
        $statement->execute(['employee_id' => $employeeId]);

        $employee = $statement->fetch();

        return $employee === false ? null : $employee;
    }

    /** @return array<string, string> */
    public function monthlySummary(int $employeeId, DateTimeImmutable $month): array
    {
        $start = $month->modify('first day of this month')->format('Y-m-d');
        $end = $month->modify('first day of next month')->format('Y-m-d');
        $statement = $this->connection->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END), 0) AS expense
             FROM personal_finance_transactions
             WHERE employee_id = :employee_id
               AND occurred_on >= :start_date
               AND occurred_on < :end_date"
        );
        $statement->execute([
            'employee_id' => $employeeId,
            'start_date' => $start,
            'end_date' => $end,
        ]);

        /** @var array<string, string> $summary */
        $summary = $statement->fetch();
        $summary['balance'] = number_format(
            (float) $summary['income'] - (float) $summary['expense'],
            2,
            '.',
            ''
        );

        return $summary;
    }

    /** @return list<array<string, mixed>> */
    public function findTransactions(int $employeeId, int $page, int $perPage, ?int $walletId = null): array
    {
        $offset = ($page - 1) * $perPage;
        $statement = $this->connection->prepare(
            "SELECT transaction.id, transaction.transaction_type, transaction.amount, transaction.occurred_on,
                    transaction.notes, category.name AS category_name, category.icon AS category_icon,
                    wallet.name AS wallet_name
             FROM personal_finance_transactions AS transaction
             INNER JOIN personal_finance_categories AS category ON category.id = transaction.category_id
             INNER JOIN personal_finance_wallets AS wallet ON wallet.id = transaction.wallet_id
             WHERE transaction.employee_id = :employee_id
               AND (:wallet_id IS NULL OR transaction.wallet_id = :filtered_wallet_id)
             ORDER BY transaction.occurred_on DESC, transaction.id DESC
             LIMIT :limit OFFSET :offset"
        );
        $statement->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $statement->bindValue(':wallet_id', $walletId, $walletId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $statement->bindValue(':filtered_wallet_id', $walletId, $walletId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        /** @var list<array<string, mixed>> $transactions */
        $transactions = $statement->fetchAll();

        return $transactions;
    }

    public function countTransactions(int $employeeId, ?int $walletId = null): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM personal_finance_transactions
             WHERE employee_id = :employee_id AND (:wallet_id IS NULL OR wallet_id = :filtered_wallet_id)'
        );
        $statement->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $statement->bindValue(':wallet_id', $walletId, $walletId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $statement->bindValue(':filtered_wallet_id', $walletId, $walletId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /** @return list<array<string, mixed>> */
    public function categorySpending(int $employeeId, DateTimeImmutable $month): array
    {
        $statement = $this->connection->prepare(
            "SELECT category.name, category.icon, SUM(transaction.amount) AS amount
             FROM personal_finance_transactions AS transaction
             INNER JOIN personal_finance_categories AS category ON category.id = transaction.category_id
             WHERE transaction.employee_id = :employee_id
               AND transaction.transaction_type = 'expense'
               AND transaction.occurred_on >= :start_date
               AND transaction.occurred_on < :end_date
             GROUP BY category.id, category.name, category.icon
             ORDER BY amount DESC, category.name ASC"
        );
        $statement->execute([
            'employee_id' => $employeeId,
            'start_date' => $month->modify('first day of this month')->format('Y-m-d'),
            'end_date' => $month->modify('first day of next month')->format('Y-m-d'),
        ]);

        return $statement->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function findWallets(int $employeeId): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, wallet_type FROM personal_finance_wallets
             WHERE employee_id = :employee_id ORDER BY created_at ASC, id ASC'
        );
        $statement->execute(['employee_id' => $employeeId]);

        return $statement->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function walletFinancials(int $employeeId, DateTimeImmutable $month): array
    {
        $currentStart = $month->modify('first day of this month')->format('Y-m-d');
        $currentEnd = $month->modify('first day of next month')->format('Y-m-d');
        $previousStart = $month->modify('first day of previous month')->format('Y-m-d');
        $statement = $this->connection->prepare(
            "SELECT wallet.id, wallet.name, wallet.wallet_type,
                    COALESCE(SUM(CASE WHEN transaction.occurred_on <= CURRENT_DATE()
                        AND transaction.transaction_type = 'income' THEN transaction.amount
                        WHEN transaction.occurred_on <= CURRENT_DATE()
                        AND transaction.transaction_type = 'expense' THEN -transaction.amount
                        ELSE 0 END), 0) AS balance,
                    COALESCE(SUM(CASE WHEN transaction.transaction_type = 'expense'
                        AND transaction.occurred_on >= :current_start
                        AND transaction.occurred_on < :current_end THEN transaction.amount ELSE 0 END), 0) AS current_spending,
                    COALESCE(SUM(CASE WHEN transaction.transaction_type = 'expense'
                        AND transaction.occurred_on >= :previous_start
                        AND transaction.occurred_on < :previous_end THEN transaction.amount ELSE 0 END), 0) AS previous_spending
             FROM personal_finance_wallets AS wallet
             LEFT JOIN personal_finance_transactions AS transaction
                ON transaction.wallet_id = wallet.id AND transaction.employee_id = wallet.employee_id
             WHERE wallet.employee_id = :employee_id
             GROUP BY wallet.id, wallet.name, wallet.wallet_type
             ORDER BY wallet.created_at ASC, wallet.id ASC"
        );
        $statement->execute([
            'employee_id' => $employeeId,
            'current_start' => $currentStart,
            'current_end' => $currentEnd,
            'previous_start' => $previousStart,
            'previous_end' => $currentStart,
        ]);

        return $statement->fetchAll();
    }

    public function walletExists(int $employeeId, int $walletId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM personal_finance_wallets WHERE id = :wallet_id AND employee_id = :employee_id'
        );
        $statement->execute(['wallet_id' => $walletId, 'employee_id' => $employeeId]);

        return $statement->fetchColumn() !== false;
    }

    /** @return list<string> */
    public function walletNames(int $employeeId): array
    {
        $statement = $this->connection->prepare(
            'SELECT name FROM personal_finance_wallets WHERE employee_id = :employee_id'
        );
        $statement->execute(['employee_id' => $employeeId]);

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function addWallet(int $employeeId, string $name, string $walletType): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO personal_finance_wallets (employee_id, name, wallet_type)
             VALUES (:employee_id, :name, :wallet_type)'
        );
        $statement->execute([
            'employee_id' => $employeeId,
            'name' => $name,
            'wallet_type' => $walletType,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public function findCategories(int $employeeId, string $transactionType): array
    {
        $statement = $this->connection->prepare(
            "SELECT id, employee_id, name, color, icon, parent_id, transaction_type, is_system
             FROM personal_finance_categories
             WHERE transaction_type = :transaction_type
               AND (employee_id IS NULL OR employee_id = :employee_id)
             ORDER BY employee_id IS NULL DESC, name ASC"
        );
        $statement->execute([
            'employee_id' => $employeeId,
            'transaction_type' => $transactionType,
        ]);

        /** @var list<array<string, mixed>> $categories */
        $categories = $statement->fetchAll();

        return $categories;
    }

    /** @return array<string, mixed>|null */
    public function findCategory(int $employeeId, int $categoryId, string $transactionType): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT id, employee_id, name, color, icon, parent_id, transaction_type, is_system
             FROM personal_finance_categories
             WHERE id = :category_id
               AND transaction_type = :transaction_type
               AND (employee_id IS NULL OR employee_id = :employee_id)"
        );
        $statement->execute([
            'category_id' => $categoryId,
            'employee_id' => $employeeId,
            'transaction_type' => $transactionType,
        ]);

        $category = $statement->fetch();

        return $category === false ? null : $category;
    }

    /** @param array<string, string|int> $category */
    public function addCategory(array $category): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO personal_finance_categories
                (employee_id, parent_id, code, name, transaction_type, icon)
             VALUES (:employee_id, :parent_id, :code, :name, :transaction_type, :icon)'
        );
        $statement->execute($category);
    }

    /** @return array<string, mixed>|null */
    public function findOwnedCategory(int $employeeId, int $categoryId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, employee_id, name, icon, parent_id, transaction_type
             FROM personal_finance_categories
             WHERE id = :category_id AND employee_id = :employee_id AND is_system = 0'
        );
        $statement->execute(['category_id' => $categoryId, 'employee_id' => $employeeId]);
        $category = $statement->fetch();

        return $category === false ? null : $category;
    }

    public function updateOwnedCategory(array $category): void
    {
        $statement = $this->connection->prepare(
            'UPDATE personal_finance_categories
             SET name = :name, icon = :icon, parent_id = :parent_id
             WHERE id = :id AND employee_id = :employee_id AND is_system = 0'
        );
        $statement->execute($category);
    }

    public function categoryHasReferences(int $categoryId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT EXISTS(SELECT 1 FROM personal_finance_transactions WHERE category_id = :transaction_category_id)
                    OR EXISTS(SELECT 1 FROM personal_finance_budget_items WHERE category_id = :budget_category_id)
                    OR EXISTS(SELECT 1 FROM personal_finance_categories WHERE parent_id = :child_category_id)'
        );
        $statement->execute([
            'transaction_category_id' => $categoryId,
            'budget_category_id' => $categoryId,
            'child_category_id' => $categoryId,
        ]);

        return (bool) $statement->fetchColumn();
    }

    public function parentChainContains(int $categoryId, int $possibleChildId): bool
    {
        $currentId = $possibleChildId;
        for ($depth = 0; $depth < 100 && $currentId > 0; $depth++) {
            if ($currentId === $categoryId) {
                return true;
            }
            $statement = $this->connection->prepare(
                'SELECT parent_id FROM personal_finance_categories WHERE id = :category_id'
            );
            $statement->execute(['category_id' => $currentId]);
            $parentId = $statement->fetchColumn();
            if ($parentId === false) {
                return false;
            }
            $currentId = (int) $parentId;
        }

        return $currentId > 0;
    }

    public function deleteOwnedCategory(int $employeeId, int $categoryId): void
    {
        $statement = $this->connection->prepare(
            'DELETE FROM personal_finance_categories
             WHERE id = :category_id AND employee_id = :employee_id AND is_system = 0'
        );
        $statement->execute(['category_id' => $categoryId, 'employee_id' => $employeeId]);
    }

    /** @param array<string, string|int> $transaction */
    public function addTransaction(array $transaction): void
    {
        $statement = $this->connection->prepare(
            "INSERT INTO personal_finance_transactions
                (employee_id, wallet_id, category_id, transaction_type, amount, occurred_on, notes)
             VALUES
                (:employee_id, :wallet_id, :category_id, :transaction_type, :amount, :occurred_on, :notes)"
        );
        $statement->execute($transaction);
    }

    /** @return array<string, mixed>|null */
    public function findTransaction(int $employeeId, int $transactionId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, wallet_id, category_id, transaction_type, amount, occurred_on, notes
             FROM personal_finance_transactions WHERE id = :id AND employee_id = :employee_id'
        );
        $statement->execute(['id' => $transactionId, 'employee_id' => $employeeId]);
        $transaction = $statement->fetch();

        return $transaction === false ? null : $transaction;
    }

    /** @param array<string, string|int|null> $transaction */
    public function updateTransaction(array $transaction): void
    {
        $statement = $this->connection->prepare(
            'UPDATE personal_finance_transactions
             SET wallet_id = :wallet_id, category_id = :category_id, transaction_type = :transaction_type, amount = :amount,
                 occurred_on = :occurred_on, notes = :notes
             WHERE id = :id AND employee_id = :employee_id'
        );
        $statement->execute($transaction);
    }

    /** @return list<array<string, mixed>> */
    public function findBudgetItems(int $employeeId, DateTimeImmutable $month): array
    {
        $statement = $this->connection->prepare(
            "SELECT category.id, category.name, category.icon,
                    COALESCE(item.amount, 0) AS budget_amount,
                    COALESCE(SUM(CASE WHEN transaction.occurred_on >= :start_date
                                      AND transaction.occurred_on < :end_date
                                      THEN transaction.amount ELSE 0 END), 0) AS actual_amount
             FROM personal_finance_categories AS category
             LEFT JOIN personal_finance_budgets AS budget
                    ON budget.employee_id = :budget_employee_id AND budget.budget_month = :budget_month
             LEFT JOIN personal_finance_budget_items AS item
                    ON item.budget_id = budget.id AND item.category_id = category.id
             LEFT JOIN personal_finance_transactions AS transaction
                    ON transaction.category_id = category.id AND transaction.employee_id = :transaction_employee_id
                       AND transaction.transaction_type = 'expense'
             WHERE category.transaction_type = 'expense'
               AND (category.employee_id IS NULL OR category.employee_id = :category_employee_id)
             GROUP BY category.id, category.name, category.icon, item.amount
             ORDER BY category.name"
        );
        $statement->execute([
            'budget_employee_id' => $employeeId,
            'transaction_employee_id' => $employeeId,
            'category_employee_id' => $employeeId,
            'budget_month' => $month->format('Y-m-01'),
            'start_date' => $month->modify('first day of this month')->format('Y-m-d'),
            'end_date' => $month->modify('first day of next month')->format('Y-m-d'),
        ]);

        return $statement->fetchAll();
    }

    /** @param array<int, string> $amounts */
    public function saveBudget(int $employeeId, DateTimeImmutable $month, array $amounts): void
    {
        $this->connection->beginTransaction();
        try {
            $header = $this->connection->prepare(
                'INSERT INTO personal_finance_budgets (employee_id, budget_month)
                 VALUES (:employee_id, :budget_month)
                 ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), updated_at = CURRENT_TIMESTAMP'
            );
            $header->execute(['employee_id' => $employeeId, 'budget_month' => $month->format('Y-m-01')]);
            $budgetId = (int) $this->connection->lastInsertId();
            $this->connection->prepare('DELETE FROM personal_finance_budget_items WHERE budget_id = :budget_id')
                ->execute(['budget_id' => $budgetId]);
            $insert = $this->connection->prepare(
                'INSERT INTO personal_finance_budget_items (budget_id, category_id, amount)
                 VALUES (:budget_id, :category_id, :amount)'
            );
            foreach ($amounts as $categoryId => $amount) {
                if (is_numeric($amount) && (float) $amount > 0) {
                    $insert->execute([
                        'budget_id' => $budgetId,
                        'category_id' => $categoryId,
                        'amount' => number_format((float) $amount, 2, '.', ''),
                    ]);
                }
            }
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    /** @return array<int, string> */
    public function budgetAmountsForMonth(int $employeeId, DateTimeImmutable $month): array
    {
        $statement = $this->connection->prepare(
            'SELECT item.category_id, item.amount FROM personal_finance_budget_items AS item
             INNER JOIN personal_finance_budgets AS budget ON budget.id = item.budget_id
             WHERE budget.employee_id = :employee_id AND budget.budget_month = :budget_month'
        );
        $statement->execute(['employee_id' => $employeeId, 'budget_month' => $month->format('Y-m-01')]);

        return $statement->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** @return list<array<string, mixed>> */
    public function weeklyTotals(int $employeeId, DateTimeImmutable $month): array
    {
        return $this->periodTotals(
            $employeeId,
            $month->modify('first day of this month'),
            $month->modify('first day of next month'),
            "CONCAT('Week ', WEEK(occurred_on, 3))"
        );
    }

    /** @return list<array<string, mixed>> */
    public function yearlyMonthlyTotals(int $employeeId, int $year): array
    {
        return $this->periodTotals(
            $employeeId,
            new DateTimeImmutable(sprintf('%d-01-01', $year)),
            new DateTimeImmutable(sprintf('%d-01-01', $year + 1)),
            "DATE_FORMAT(occurred_on, '%Y-%m')"
        );
    }

    /** @return list<array<string, mixed>> */
    private function periodTotals(int $employeeId, DateTimeImmutable $start, DateTimeImmutable $end, string $periodExpression): array
    {
        $statement = $this->connection->prepare(
            "SELECT {$periodExpression} AS period,
                    COALESCE(SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END), 0) AS income,
                    COALESCE(SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END), 0) AS expense
             FROM personal_finance_transactions
             WHERE employee_id = :employee_id AND occurred_on >= :start_date AND occurred_on < :end_date
             GROUP BY {$periodExpression} ORDER BY MIN(occurred_on)"
        );
        $statement->execute([
            'employee_id' => $employeeId,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ]);

        return $statement->fetchAll();
    }
}
