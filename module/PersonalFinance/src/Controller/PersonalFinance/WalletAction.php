<?php

declare(strict_types=1);

namespace PersonalFinance\Controller\PersonalFinance;

use InvalidArgumentException;
use PersonalFinance\Controller\PersonalFinanceController;
use PersonalFinance\Model\PersonalFinanceModel;

class WalletAction
{
    public function create(PersonalFinanceController $controller, PersonalFinanceModel $model)
    {
        if (!$controller->getRequest()->isPost()) {
            return $controller->redirect()->toRoute('personal-finance', [], ['query' => ['tab' => 'wallets']]);
        }

        $month = $model->parseMonth($controller->params()->fromPost('month'));
        $query = ['tab' => 'wallets', 'month' => $month->format('Y-m')];
        $employee = $model->currentEmployee();
        if ($employee === null) {
            $query['notice'] = 'no_employee';
        } else {
            try {
                $query['wallet'] = $model->createPresetWallet(
                    (int) $employee['id'],
                    (string) $controller->params()->fromPost('preset')
                );
                $query['notice'] = 'wallet_saved';
            } catch (InvalidArgumentException $exception) {
                $query['notice'] = 'wallet_invalid';
            }
        }

        return $controller->redirect()->toRoute('personal-finance', [], ['query' => $query]);
    }
}
