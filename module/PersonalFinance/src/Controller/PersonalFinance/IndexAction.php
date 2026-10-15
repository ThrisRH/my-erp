<?php

declare(strict_types=1);

namespace PersonalFinance\Controller\PersonalFinance;

use Laminas\View\Model\ViewModel;
use PersonalFinance\Controller\PersonalFinanceController;
use PersonalFinance\Model\PersonalFinanceModel;

class IndexAction
{
    public function handle(PersonalFinanceController $controller, PersonalFinanceModel $model): ViewModel
    {
        $month = $model->parseMonth($controller->params()->fromQuery('month'));
        $tab = (string) $controller->params()->fromQuery('tab', 'overview');
        if (!in_array($tab, ['overview', 'transactions', 'budget', 'categories'], true)) {
            $tab = 'overview';
        }

        $page = max(1, (int) $controller->params()->fromQuery('page', 1));
        $employee = $model->currentEmployee();
        if ($employee === null) {
            return $this->view([
                'employee' => null,
                'month' => $month,
                'dashboard' => null,
                'notice' => $controller->params()->fromQuery('notice'),
                'tab' => $tab,
            ]);
        }

        return $this->view([
            'employee' => $employee,
            'month' => $month,
            'dashboard' => $model->dashboard((int) $employee['id'], $month, $tab, $page),
            'notice' => $controller->params()->fromQuery('notice'),
            'tab' => $tab,
            'editingTransaction' => $model->transactionForEdit(
                (int) $employee['id'],
                $controller->params()->fromQuery('edit')
            ),
        ]);
    }

    /** @param array<string, mixed> $variables */
    private function view(array $variables): ViewModel
    {
        return (new ViewModel($variables))->setTemplate('personal-finance/dashboard/index');
    }
}
