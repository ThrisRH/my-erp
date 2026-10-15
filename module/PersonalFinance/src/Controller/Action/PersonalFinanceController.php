<?php

declare(strict_types=1);

namespace PersonalFinance\Controller\Action;

use InvalidArgumentException;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use PersonalFinance\Model\PersonalFinanceModel;

class PersonalFinanceController extends AbstractActionController
{
    private ?PersonalFinanceModel $model = null;

    public function indexAction(): ViewModel
    {
        $month = $this->model()->parseMonth($this->params()->fromQuery('month'));
        $tab = (string) $this->params()->fromQuery('tab', 'overview');
        if (!in_array($tab, ['overview', 'transactions', 'budget', 'categories'], true)) {
            $tab = 'overview';
        }
        $page = max(1, (int) $this->params()->fromQuery('page', 1));
        $employee = $this->model()->currentEmployee();

        if ($employee === null) {
            return new ViewModel([
                'employee' => null,
                'month' => $month,
                'dashboard' => null,
                'notice' => $this->params()->fromQuery('notice'),
                'tab' => $tab,
            ]);
        }

        return new ViewModel([
            'employee' => $employee,
            'month' => $month,
            'dashboard' => $this->model()->dashboard((int) $employee['id'], $month, $tab, $page),
            'notice' => $this->params()->fromQuery('notice'),
            'tab' => $tab,
            'editingTransaction' => $this->model()->transactionForEdit(
                (int) $employee['id'],
                $this->params()->fromQuery('edit')
            ),
        ]);
    }

    public function addAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('personal-finance');
        }

        $month = $this->model()->parseMonth($this->params()->fromPost('month'));
        $employee = $this->model()->currentEmployee();

        if ($employee === null) {
            $notice = 'no_employee';
        } else {
            try {
                $this->model()->addTransaction((int) $employee['id'], $this->params()->fromPost());
                $notice = 'saved';
            } catch (InvalidArgumentException $exception) {
                $notice = 'invalid';
            }
        }

        return $this->redirect()->toRoute('personal-finance', [], [
            'query' => [
                'month' => $month->format('Y-m'),
                'tab' => 'transactions',
                'notice' => $notice,
            ],
        ]);
    }

    public function updateAction()
    {
        return $this->handleTransactionAction(true);
    }

    public function addCategoryAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('personal-finance');
        }
        $month = $this->model()->parseMonth($this->params()->fromPost('month'));
        $employee = $this->model()->currentEmployee();
        $notice = 'no_employee';
        if ($employee !== null) {
            try {
                $this->model()->addCategory((int) $employee['id'], $this->params()->fromPost());
                $notice = 'category_saved';
            } catch (InvalidArgumentException $exception) {
                $notice = 'category_invalid';
            }
        }
        return $this->redirect()->toRoute('personal-finance', [], [
            'query' => ['month' => $month->format('Y-m'), 'tab' => 'categories', 'notice' => $notice],
        ]);
    }

    public function saveBudgetAction()
    {
        return $this->handleBudgetAction(false);
    }

    public function cloneBudgetAction()
    {
        return $this->handleBudgetAction(true);
    }

    private function handleTransactionAction(bool $isUpdate)
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('personal-finance');
        }
        $month = $this->model()->parseMonth($this->params()->fromPost('month'));
        $employee = $this->model()->currentEmployee();
        $notice = 'no_employee';
        if ($employee !== null) {
            try {
                if ($isUpdate) {
                    $this->model()->updateTransaction((int) $employee['id'], $this->params()->fromPost());
                    $notice = 'updated';
                } else {
                    $this->model()->addTransaction((int) $employee['id'], $this->params()->fromPost());
                    $notice = 'saved';
                }
            } catch (InvalidArgumentException $exception) {
                $notice = 'invalid';
            }
        }
        return $this->redirect()->toRoute('personal-finance', [], ['query' => ['month' => $month->format('Y-m'), 'tab' => 'transactions', 'notice' => $notice]]);
    }

    private function handleBudgetAction(bool $clone)
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('personal-finance');
        }
        $month = $this->model()->parseMonth($this->params()->fromPost('month'));
        $employee = $this->model()->currentEmployee();
        $notice = 'no_employee';
        if ($employee !== null) {
            try {
                if ($clone) {
                    $this->model()->cloneBudget((int) $employee['id'], $month);
                    $notice = 'budget_cloned';
                } else {
                    $this->model()->saveBudget((int) $employee['id'], $month, $this->params()->fromPost());
                    $notice = 'budget_saved';
                }
            } catch (InvalidArgumentException $exception) {
                $notice = 'budget_invalid';
            }
        }
        return $this->redirect()->toRoute('personal-finance', [], ['query' => ['month' => $month->format('Y-m'), 'tab' => 'budget', 'notice' => $notice]]);
    }

    private function model(): PersonalFinanceModel
    {
        if ($this->model !== null) {
            return $this->model;
        }

        $serviceManager = $this->getEvent()->getApplication()->getServiceManager();
        /** @var array<string, mixed> $config */
        $config = $serviceManager->get('config');

        return $this->model = PersonalFinanceModel::fromConfig(
            $config['personal_finance']['database'] ?? []
        );
    }
}
