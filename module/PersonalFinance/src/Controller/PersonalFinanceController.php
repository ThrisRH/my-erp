<?php

declare(strict_types=1);

namespace PersonalFinance\Controller;

use Application\Helper\Access;
use Application\Model\AccountModel;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use PersonalFinance\Controller\PersonalFinance\BudgetAction;
use PersonalFinance\Controller\PersonalFinance\CategoryAction;
use PersonalFinance\Controller\PersonalFinance\IndexAction;
use PersonalFinance\Controller\PersonalFinance\TransactionAction;
use PersonalFinance\Controller\PersonalFinance\WalletAction;
use PersonalFinance\Model\PersonalFinanceModel;

class PersonalFinanceController extends AbstractActionController
{
    private ?PersonalFinanceModel $model = null;

    public function indexAction(): ViewModel
    {
        return (new IndexAction())->handle($this, $this->model());
    }

    public function addAction()
    {
        return (new TransactionAction())->add($this, $this->model());
    }

    public function updateAction()
    {
        return (new TransactionAction())->update($this, $this->model());
    }

    public function createWalletAction()
    {
        return (new WalletAction())->create($this, $this->model());
    }

    public function updateWalletAction()
    {
        return (new WalletAction())->update($this, $this->model());
    }

    public function deleteWalletAction()
    {
        return (new WalletAction())->delete($this, $this->model());
    }

    public function addCategoryAction()
    {
        return (new CategoryAction())->handle($this, $this->model());
    }

    public function updateCategoryAction()
    {
        return (new CategoryAction())->update($this, $this->model());
    }

    public function deleteCategoryAction()
    {
        return (new CategoryAction())->delete($this, $this->model());
    }

    public function saveBudgetAction()
    {
        return (new BudgetAction())->save($this, $this->model());
    }

    public function cloneBudgetAction()
    {
        return (new BudgetAction())->clone($this, $this->model());
    }

    public function model(): PersonalFinanceModel
    {
        if ($this->model !== null) {
            return $this->model;
        }

        $serviceManager = $this->getEvent()->getApplication()->getServiceManager();
        /** @var array<string, mixed> $config */
        $config = $serviceManager->get('config');

        $database = $config['personal_finance']['database'] ?? [];
        $account = (new Access(new AccountModel($database)))->account();
        if ($account === null) {
            throw new \RuntimeException('Authentication required.');
        }
        return $this->model = PersonalFinanceModel::fromConfig($database, (int) $account['employee_id']);
    }
}
