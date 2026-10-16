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

    public function update(PersonalFinanceController $controller, PersonalFinanceModel $model)
    {
        return $this->maintain($controller, $model, false);
    }

    public function delete(PersonalFinanceController $controller, PersonalFinanceModel $model)
    {
        return $this->maintain($controller, $model, true);
    }

    private function maintain(PersonalFinanceController $controller, PersonalFinanceModel $model, bool $delete)
    {
        if (!$controller->getRequest()->isPost()) {
            return $controller->redirect()->toRoute('personal-finance');
        }

        $month = $model->parseMonth($controller->params()->fromPost('month'));
        $employee = $model->currentEmployee();
        $notice = 'no_employee';
        if ($employee !== null) {
            try {
                if ($delete) {
                    $model->deleteCategory((int) $employee['id'], $controller->params()->fromPost('id'));
                    $notice = 'category_deleted';
                } else {
                    $model->updateCategory((int) $employee['id'], $controller->params()->fromPost());
                    $notice = 'category_updated';
                }
            } catch (InvalidArgumentException $exception) {
                $notice = $exception->getMessage() === 'Category is in use.'
                    ? 'category_in_use' : 'category_invalid';
            }
        }

        return $controller->redirect()->toRoute('personal-finance', [], [
            'query' => ['month' => $month->format('Y-m'), 'tab' => 'categories', 'notice' => $notice],
        ]);
    }
}
