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

    public function update(PersonalFinanceController $controller, PersonalFinanceModel $model)
    {
        $isAjax = $controller->getRequest()->isXmlHttpRequest()
            || str_contains((string) $controller->getRequest()->getHeader('Accept'), 'application/json');

        if (!$controller->getRequest()->isPost()) {
            if ($isAjax) {
                $response = $controller->getResponse();
                $response->getHeaders()->addHeaderLine('Content-Type', 'application/json; charset=utf-8');
                $response->setContent(json_encode(['success' => false, 'error' => 'Method not allowed']));
                return $response;
            }
            return $controller->redirect()->toRoute('personal-finance', [], ['query' => ['tab' => 'wallets']]);
        }

        $month = $model->parseMonth($controller->params()->fromPost('month'));
        $walletId = filter_var($controller->params()->fromPost('id'), FILTER_VALIDATE_INT);
        $query = ['tab' => 'wallets', 'month' => $month->format('Y-m')];
        if ($walletId !== false && $walletId > 0) {
            $query['wallet'] = $walletId;
        }

        $employee = $model->currentEmployee();
        $notice = 'no_employee';
        $errorMsg = null;
        if ($employee === null) {
            $notice = 'no_employee';
            $errorMsg = 'No active employee';
        } else {
            try {
                $model->updateWallet((int) $employee['id'], $controller->params()->fromPost());
                $notice = 'wallet_updated';
            } catch (InvalidArgumentException $exception) {
                $notice = $exception->getMessage() === 'A wallet with this name already exists.'
                    ? 'wallet_name_exists' : 'wallet_invalid';
                $errorMsg = $exception->getMessage();
            }
        }

        if ($isAjax) {
            $response = $controller->getResponse();
            $response->getHeaders()->addHeaderLine('Content-Type', 'application/json; charset=utf-8');
            $response->setContent(json_encode([
                'success' => $notice === 'wallet_updated',
                'notice' => $notice,
                'name' => trim((string) $controller->params()->fromPost('name')),
                'id' => $walletId,
                'error' => $errorMsg,
            ]));
            return $response;
        }

        $query['notice'] = $notice;
        return $controller->redirect()->toRoute('personal-finance', [], ['query' => $query]);
    }

    public function delete(PersonalFinanceController $controller, PersonalFinanceModel $model)
    {
        $isAjax = $controller->getRequest()->isXmlHttpRequest()
            || str_contains((string) $controller->getRequest()->getHeader('Accept'), 'application/json');

        if (!$controller->getRequest()->isPost()) {
            if ($isAjax) {
                $response = $controller->getResponse();
                $response->getHeaders()->addHeaderLine('Content-Type', 'application/json; charset=utf-8');
                $response->setContent(json_encode(['success' => false, 'error' => 'Method not allowed']));
                return $response;
            }
            return $controller->redirect()->toRoute('personal-finance', [], ['query' => ['tab' => 'wallets']]);
        }

        $month = $model->parseMonth($controller->params()->fromPost('month'));
        $walletId = filter_var($controller->params()->fromPost('id'), FILTER_VALIDATE_INT);
        $query = ['tab' => 'wallets', 'month' => $month->format('Y-m')];
        $employee = $model->currentEmployee();
        $notice = 'no_employee';
        $errorMsg = null;
        if ($employee === null) {
            $notice = 'no_employee';
            $errorMsg = 'No active employee';
        } else {
            try {
                $model->deleteWallet((int) $employee['id'], $controller->params()->fromPost('id'));
                $notice = 'wallet_deleted';
            } catch (InvalidArgumentException $exception) {
                $notice = $exception->getMessage() === 'Wallet is in use.'
                    ? 'wallet_in_use' : 'wallet_invalid';
                $errorMsg = $exception->getMessage();
            }
        }

        if ($isAjax) {
            $response = $controller->getResponse();
            $response->getHeaders()->addHeaderLine('Content-Type', 'application/json; charset=utf-8');
            $response->setContent(json_encode([
                'success' => $notice === 'wallet_deleted',
                'notice' => $notice,
                'id' => $walletId,
                'error' => $errorMsg,
            ]));
            return $response;
        }

        $query['notice'] = $notice;
        return $controller->redirect()->toRoute('personal-finance', [], ['query' => $query]);
    }
}
