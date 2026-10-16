<?php

declare(strict_types=1);

namespace PersonalFinance\Controller\PersonalFinance;

use InvalidArgumentException;
use PersonalFinance\Controller\PersonalFinanceController;
use PersonalFinance\Model\PersonalFinanceModel;

class CategoryAction
{
    public function handle(PersonalFinanceController $controller, PersonalFinanceModel $model)
    {
        if (!$controller->getRequest()->isPost()) {
            return $controller->redirect()->toRoute('personal-finance');
        }

        $month = $model->parseMonth($controller->params()->fromPost('month'));
        $employee = $model->currentEmployee();
        $notice = 'no_employee';
        if ($employee !== null) {
            try {
                $model->addCategory((int) $employee['id'], $controller->params()->fromPost());
                $notice = 'category_saved';
            } catch (InvalidArgumentException $exception) {
                $notice = 'category_invalid';
            }
        }

        return $controller->redirect()->toRoute('personal-finance', [], [
            'query' => ['month' => $month->format('Y-m'), 'tab' => 'categories', 'notice' => $notice],
        ]);
    }
}
