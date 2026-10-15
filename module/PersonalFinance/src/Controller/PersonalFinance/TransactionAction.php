<?php

declare(strict_types=1);

namespace PersonalFinance\Controller\PersonalFinance;

use InvalidArgumentException;
use PersonalFinance\Controller\PersonalFinanceController;
use PersonalFinance\Model\PersonalFinanceModel;

class TransactionAction
{
    public function add(PersonalFinanceController $controller, PersonalFinanceModel $model)
    {
        return $this->save($controller, $model, false);
    }

    public function update(PersonalFinanceController $controller, PersonalFinanceModel $model)
    {
        return $this->save($controller, $model, true);
    }

    private function save(PersonalFinanceController $controller, PersonalFinanceModel $model, bool $isUpdate)
    {
        if (!$controller->getRequest()->isPost()) {
            return $controller->redirect()->toRoute('personal-finance');
        }

        $month = $model->parseMonth($controller->params()->fromPost('month'));
        $employee = $model->currentEmployee();
        $notice = 'no_employee';
        if ($employee !== null) {
            try {
                if ($isUpdate) {
                    $model->updateTransaction((int) $employee['id'], $controller->params()->fromPost());
                    $notice = 'updated';
                } else {
                    $model->addTransaction((int) $employee['id'], $controller->params()->fromPost());
                    $notice = 'saved';
                }
            } catch (InvalidArgumentException $exception) {
                $notice = 'invalid';
            }
        }

        return $controller->redirect()->toRoute('personal-finance', [], [
            'query' => ['month' => $month->format('Y-m'), 'tab' => 'transactions', 'notice' => $notice],
        ]);
    }
}
