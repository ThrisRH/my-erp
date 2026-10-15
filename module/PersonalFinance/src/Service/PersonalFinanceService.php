<?php

declare(strict_types=1);

namespace PersonalFinance\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use PersonalFinance\Repository\PersonalFinanceRepository;

class PersonalFinanceService
{
    private PersonalFinanceRepository $repository;

    public function __construct(PersonalFinanceRepository $repository)
    {
        $this->repository = $repository;
    }

    /** @return array<string, mixed>|null */
    public function currentEmployee(): ?array
    {
        // Authentication will replace this temporary employee resolver.
        return $this->repository->findCurrentEmployee();
    }

    /** @return array{summary: array<string, string>, transactions: list<array<string, mixed>>, incomeCategories: list<array<string, mixed>>, expenseCategories: list<array<string, mixed>>} */
    public function dashboard(int $employeeId, DateTimeImmutable $month, string $tab, int $page): array
    {
        $dashboard = [
            'summary' => $this->repository->monthlySummary($employeeId, $month),
            'incomeCategories' => $this->repository->findCategories($employeeId, 'income'),
            'expenseCategories' => $this->repository->findCategories($employeeId, 'expense'),
            'wallets' => $this->repository->findWallets($employeeId),
        ];
        if ($tab === 'transactions') {
            $count = $this->repository->countTransactions($employeeId);
            $pages = max(1, (int) ceil($count / 12));
            $page = min(max(1, $page), $pages);
            $dashboard['transactions'] = $this->repository->findTransactions($employeeId, $page, 12);
            $dashboard['pages'] = $pages;
            $dashboard['page'] = $page;
            $dashboard['count'] = $count;
        } elseif ($tab === 'budget') {
            $items = $this->repository->findBudgetItems($employeeId, $month);
            $dashboard['budgetItems'] = $items;
            $dashboard['budgetSummary'] = $this->budgetSummary($items);
        } elseif ($tab === 'overview') {
            $dashboard['weeklyTrend'] = $this->decorateTrend($this->repository->weeklyTotals($employeeId, $month));
            $dashboard['monthlyTrend'] = $this->decorateTrend(
                $this->repository->yearlyMonthlyTotals($employeeId, (int) $month->format('Y'))
            );
            $dashboard['categorySpending'] = $this->repository->categorySpending($employeeId, $month);
        }

        return $dashboard;
    }

    /** @param array<string, mixed> $input */
    public function addCategory(int $employeeId, array $input): void
    {
        $type = (string) ($input['transaction_type'] ?? '');
        $name = trim((string) ($input['name'] ?? ''));
        $icon = (string) ($input['icon'] ?? 'folder');
        $parentId = filter_var($input['parent_id'] ?? 0, FILTER_VALIDATE_INT);
        if (!in_array($type, ['income', 'expense'], true) || $name === '' || strlen($name) > 100
            || !in_array($icon, CategoryIcons::names(), true) || $parentId === false || $parentId < 0
            || ($parentId > 0 && $this->repository->findCategory($employeeId, $parentId, $type) === null)
        ) {
            throw new InvalidArgumentException('Invalid category details.');
        }
        $this->repository->addCategory([
            'employee_id' => $employeeId,
            'parent_id' => $parentId,
            'code' => 'employee_' . $employeeId . '_' . bin2hex(random_bytes(8)),
            'name' => $name,
            'transaction_type' => $type,
            'icon' => $icon,
        ]);
    }

    public function parseMonth(?string $month): DateTimeImmutable
    {
        $month ??= date('Y-m');
        $parsed = DateTimeImmutable::createFromFormat('!Y-m', $month);

        if ($parsed === false || $parsed->format('Y-m') !== $month) {
            return new DateTimeImmutable('first day of this month');
        }

        return $parsed;
    }

    /** @param array<string, mixed> $input */
    public function addTransaction(int $employeeId, array $input): void
    {
        $this->repository->addTransaction($this->validatedTransaction($employeeId, $input));
    }

    /** @return array<string, mixed>|null */
    public function transactionForEdit(int $employeeId, ?string $transactionId): ?array
    {
        $id = filter_var($transactionId, FILTER_VALIDATE_INT);

        return $id !== false && $id > 0 ? $this->repository->findTransaction($employeeId, $id) : null;
    }

    /** @param array<string, mixed> $input */
    public function updateTransaction(int $employeeId, array $input): void
    {
        $transactionId = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        if ($transactionId === false || $transactionId <= 0
            || $this->repository->findTransaction($employeeId, $transactionId) === null
        ) {
            throw new InvalidArgumentException('Transaction was not found.');
        }

        $payload = $this->validatedTransaction($employeeId, $input);
        $payload['id'] = $transactionId;
        $this->repository->updateTransaction($payload);
    }

    /** @param array<string, mixed> $input */
    public function saveBudget(int $employeeId, DateTimeImmutable $month, array $input): void
    {
        $amounts = $input['amounts'] ?? [];
        if (!is_array($amounts)) {
            throw new InvalidArgumentException('Budget amounts are invalid.');
        }

        $safeAmounts = [];
        foreach ($amounts as $categoryId => $amount) {
            $id = filter_var($categoryId, FILTER_VALIDATE_INT);
            if ($id === false || $id <= 0 || !is_numeric($amount) || (float) $amount < 0) {
                throw new InvalidArgumentException('Budget amounts must be zero or greater.');
            }
            $safeAmounts[$id] = (string) $amount;
        }
        $this->repository->saveBudget($employeeId, $month, $safeAmounts);
    }

    public function cloneBudget(int $employeeId, DateTimeImmutable $month): void
    {
        $previous = $month->modify('first day of previous month');
        $amounts = $this->repository->budgetAmountsForMonth($employeeId, $previous);
        $this->repository->saveBudget($employeeId, $month, $amounts);
    }

    /** @param list<array<string, mixed>> $budgetItems @return array<string, float> */
    private function budgetSummary(array $budgetItems): array
    {
        $budget = 0.0;
        $actual = 0.0;
        foreach ($budgetItems as $item) {
            $budget += (float) $item['budget_amount'];
            $actual += (float) $item['actual_amount'];
        }

        return ['budget' => $budget, 'actual' => $actual, 'remaining' => $budget - $actual];
    }

    /** @param list<array<string, mixed>> $periods @return list<array<string, mixed>> */
    private function decorateTrend(array $periods): array
    {
        if ($periods === []) {
            return [];
        }
        $firstExpense = (float) $periods[0]['expense'];
        $previousExpense = null;
        foreach ($periods as &$period) {
            $expense = (float) $period['expense'];
            $period['vs_previous'] = $previousExpense === null ? 0.0 : $this->percentageChange($expense, $previousExpense);
            $period['vs_first'] = $this->percentageChange($expense, $firstExpense);
            $period['direction'] = $period['vs_previous'] <= 0 ? 'positive' : 'negative';
            $previousExpense = $expense;
        }
        unset($period);

        return $periods;
    }

    private function percentageChange(float $current, float $previous): float
    {
        if ($previous === 0.0) {
            return $current === 0.0 ? 0.0 : 100.0;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    /** @param array<string, mixed> $input @return array<string, string|int|null> */
    private function validatedTransaction(int $employeeId, array $input): array
    {
        $transactionType = (string) ($input['transaction_type'] ?? '');
        if (!in_array($transactionType, ['income', 'expense'], true)) {
            throw new InvalidArgumentException('Choose either income or expense.');
        }
        $amount = (string) ($input['amount'] ?? '');
        $categoryId = filter_var($input['category_id'] ?? null, FILTER_VALIDATE_INT);
        $occurredOn = (string) ($input['occurred_on'] ?? '');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $occurredOn);
        if (!is_numeric($amount) || (float) $amount <= 0 || $categoryId === false || $categoryId <= 0
            || $date === false || $date->format('Y-m-d') !== $occurredOn
            || $this->repository->findCategory($employeeId, $categoryId, $transactionType) === null
        ) {
            throw new InvalidArgumentException('Check the transaction details.');
        }

        $notes = trim((string) ($input['notes'] ?? ''));
        if (strlen($notes) > 1000) {
            throw new InvalidArgumentException('Notes cannot exceed 1,000 characters.');
        }
        $walletId = $this->walletIdForTransaction($employeeId, $input['wallet_id'] ?? null);
        if ($walletId === null) {
            throw new InvalidArgumentException('Choose a valid wallet.');
        }

        return [
            'employee_id' => $employeeId,
            'wallet_id' => $walletId,
            'category_id' => $categoryId,
            'transaction_type' => $transactionType,
            'amount' => number_format((float) $amount, 2, '.', ''),
            'occurred_on' => $date->format('Y-m-d'),
            'notes' => $notes === '' ? null : $notes,
        ];
    }

    /** @param mixed $walletInput */
    private function walletIdForTransaction(int $employeeId, $walletInput): ?int
    {
        $quickWallets = [
            'quick:cash' => ['Tiền mặt', 'cash'],
            'quick:bank' => ['Ngân hàng', 'bank'],
            'quick:e_wallet' => ['Ví điện tử', 'e_wallet'],
        ];
        $value = (string) $walletInput;
        if (isset($quickWallets[$value])) {
            [$baseName, $type] = $quickWallets[$value];

            return $this->repository->addWallet(
                $employeeId,
                $this->nextWalletName($baseName, $this->repository->walletNames($employeeId)),
                $type
            );
        }

        $walletId = filter_var($walletInput, FILTER_VALIDATE_INT);

        return $walletId !== false && $walletId > 0 && $this->repository->walletExists($employeeId, $walletId)
            ? $walletId
            : null;
    }

    /** @param list<string> $existingNames */
    private function nextWalletName(string $baseName, array $existingNames): string
    {
        $names = array_flip($existingNames);
        if (!isset($names[$baseName])) {
            return $baseName;
        }

        $suffix = 1;
        do {
            $candidate = $baseName . ' ' . $suffix++;
        } while (isset($names[$candidate]));

        return $candidate;
    }
}
