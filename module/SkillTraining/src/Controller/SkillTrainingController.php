<?php

declare(strict_types=1);

namespace SkillTraining\Controller;

use Application\Helper\Access;
use Application\Model\AccountModel;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use RuntimeException;
use SkillTraining\Model\SkillTrainingModel;

class SkillTrainingController extends AbstractActionController
{
    private ?SkillTrainingModel $model = null;

    public function indexAction(): ViewModel
    {
        $account = $this->account();
        return (new ViewModel([
            'dashboard' => $this->model()->dashboard((int) $account['id']),
            'csrfToken' => Access::csrfToken(),
        ]))->setTemplate('skill-training/index');
    }

    public function startAction()
    {
        return $this->postJson(function (int $accountId): array {
            $session = $this->model()->start($accountId, $this->params()->fromPost());
            return ['success' => true, 'session' => $session];
        });
    }

    public function questionAction()
    {
        return $this->postJson(function (int $accountId): array {
            $sessionId = $this->positiveInt($this->params()->fromPost('session_id'));
            return ['success' => true, 'attempt' => $this->publicAttempt(
                $this->model()->question($accountId, $sessionId)
            )];
        });
    }

    public function answerAction()
    {
        return $this->postJson(function (int $accountId): array {
            $sessionId = $this->positiveInt($this->params()->fromPost('session_id'));
            $attemptId = $this->positiveInt($this->params()->fromPost('attempt_id'));
            $result = $this->model()->submit(
                $accountId,
                $sessionId,
                $attemptId,
                (string) $this->params()->fromPost('answer', '')
            );
            return [
                'success' => true,
                'attempt' => $this->publicAttempt($result['attempt']),
                'next' => $result['next'],
                'next_session' => $result['next_session'],
            ];
        });
    }

    public function dispatchAction()
    {
        return $this->postJson(function (int $accountId): array {
            return ['success' => true, 'attempt' => $this->publicAttempt($this->model()->dispatch(
                $accountId,
                $this->positiveInt($this->params()->fromPost('session_id')),
                $this->positiveInt($this->params()->fromPost('attempt_id'))
            ))];
        });
    }

    public function retryAction()
    {
        return $this->postJson(function (int $accountId): array {
            $attemptId = $this->positiveInt($this->params()->fromPost('attempt_id'));
            $result = $this->model()->retryEvaluation($accountId, $attemptId);
            return [
                'success' => true,
                'attempt' => $this->publicAttempt($result['attempt']),
                'next_session' => $result['next_session'],
            ];
        });
    }

    public function timeoutAction()
    {
        return $this->postJson(function (int $accountId): array {
            $this->model()->timeout(
                $accountId,
                $this->positiveInt($this->params()->fromPost('session_id')),
                $this->positiveInt($this->params()->fromPost('attempt_id'))
            );
            return ['success' => true, 'session_ended' => true];
        });
    }

    public function heartbeatAction()
    {
        return $this->postJson(function (int $accountId): array {
            return ['success' => $this->model()->heartbeat(
                $accountId,
                $this->positiveInt($this->params()->fromPost('session_id'))
            )];
        });
    }

    public function stopAction()
    {
        return $this->postJson(function (int $accountId): array {
            $this->model()->stop($accountId, $this->positiveInt($this->params()->fromPost('session_id')));
            return ['success' => true];
        });
    }

    private function postJson(callable $handler)
    {
        if (!$this->getRequest()->isPost()) {
            return $this->json(['success' => false, 'error' => 'Method not allowed.'], 405);
        }
        try {
            $account = $this->account();
            return $this->json($handler((int) $account['id']));
        } catch (RuntimeException $error) {
            return $this->json(['success' => false, 'error' => $error->getMessage()], 422);
        } catch (\Throwable $error) {
            error_log(sprintf(
                'Skill Training request failed: %s in %s:%d',
                $error->getMessage(),
                $error->getFile(),
                $error->getLine()
            ));
            return $this->json(['success' => false, 'error' => 'The training request could not be completed.'], 500);
        }
    }

    private function json(array $payload, int $status = 200)
    {
        $response = $this->getResponse();
        $response->setStatusCode($status);
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json; charset=utf-8');
        $response->getHeaders()->addHeaderLine('Cache-Control', 'no-store');
        $response->setContent(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
        return $response;
    }

    private function account(): array
    {
        $serviceManager = $this->getEvent()->getApplication()->getServiceManager();
        $config = $serviceManager->get('config');
        $account = (new Access(new AccountModel($config['personal_finance']['database'])))->account();
        if ($account === null) {
            throw new RuntimeException('Authentication required.');
        }
        return $account;
    }

    private function model(): SkillTrainingModel
    {
        if ($this->model === null) {
            $config = $this->getEvent()->getApplication()->getServiceManager()->get('config');
            $this->model = new SkillTrainingModel($config['personal_finance']['database'] ?? []);
        }
        return $this->model;
    }

    private function positiveInt($value): int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        if ($integer === false || $integer < 1) {
            throw new RuntimeException('A valid session or attempt is required.');
        }
        return $integer;
    }

    private function publicAttempt(array $attempt): array
    {
        unset($attempt['expected_answer']);
        return $attempt;
    }
}
