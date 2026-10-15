<?php

declare(strict_types=1);

namespace PersonalFinance\Controller\PersonalFinance;

use InvalidArgumentException;
use PersonalFinance\Controller\PersonalFinanceController;
use PersonalFinance\Model\PersonalFinanceModel;

class BudgetAction
{
    public function save(PersonalFinanceController $controller, PersonalFinanceModel $model)
    {
        return $this->handle($controller, $model, false);
    }

    public function clone(PersonalFinanceController $controller, PersonalFinanceModel $model)
    {
        return $this->handle($controller, $model, true);
    }

    private function handle(PersonalFinanceController $controller, PersonalFinanceModel $model, bool $clone)
    {
        if (!$controller->getRequest()->isPost()) {
            return $controller->redirect()->toRoute('personal-finance');
        }

        $month = $model->parseMonth($controller->params()->fromPost('month'));
        $employee = $model->currentEmployee();
        $notice = 'no_employee';
        if ($employee !== null) {
            try {
                if ($clone) {
                    $model->cloneBudget((int) $employee['id'], $month);
                    $notice = 'budget_cloned';
                } else {
                    $model->saveBudget((int) $employee['id'], $month, $controller->params()->fromPost());
                    $notice = 'budget_saved';
                }
            } catch (InvalidArgumentException $exception) {
                $notice = 'budget_invalid';
            }
        }

        return $controller->redirect()->toRoute('personal-finance', [], [
            'query' => ['month' => $month->format('Y-m'), 'tab' => 'budget', 'notice' => $notice],
        ]);
    }
}
