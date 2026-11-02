<?php

declare(strict_types=1);

namespace SkillTraining\Model;

use RuntimeException;
use PDOException;
use SkillTraining\Service\GeminiClient;

class SkillTrainingModel
{
    private SkillTrainingRepository $repository;
    private GeminiClient $gemini;

    public function __construct(array $database, ?GeminiClient $gemini = null)
    {
        $this->repository = new SkillTrainingRepository($database);
        $this->gemini = $gemini ?? new GeminiClient();
    }

    public function dashboard(int $accountId): array
    {
        $this->repository->stopStaleSessions($accountId);
        return $this->repository->dashboard($accountId);
    }

    public function start(int $accountId, array $input): array
    {
        $keywords = trim((string) ($input['keywords'] ?? ''));
        $level = (string) ($input['skill_level'] ?? '');
        $language = (string) ($input['training_language'] ?? '');
        $minimum = filter_var($input['min_interval_minutes'] ?? null, FILTER_VALIDATE_INT);
        $maximum = filter_var($input['max_interval_minutes'] ?? null, FILTER_VALIDATE_INT);
        $duration = filter_var($input['answer_duration_seconds'] ?? null, FILTER_VALIDATE_INT);
        if ($keywords === '' || mb_strlen($keywords) > 500 || !in_array($level, ['basic', 'advance'], true)
            || !in_array($language, ['en', 'vi'], true)
            || $minimum === false || $maximum === false || $minimum < 1 || $maximum > 120 || $minimum > $maximum
            || $duration === false || $duration < 5 || $duration > 600) {
            throw new RuntimeException('Check your keywords and training settings.');
        }

        
        return $this->repository->startSession($accountId, [
            'keywords' => $keywords,
            'skill_level' => $level,
            'training_language' => $language,
            'min_interval_minutes' => $minimum,
            'max_interval_minutes' => $maximum,
            'answer_duration_seconds' => $duration,
        ]);
    }

    public function question(int $accountId, int $sessionId): array
    {
        $session = $this->repository->sessionById($accountId, $sessionId);
        if (!$session || $session['status'] !== 'active') {
            throw new RuntimeException('This training session has ended.');
        }
        if ((int) $session['next_question_at'] > time()) {
            throw new RuntimeException('Your next question is not ready yet.');
        }
        $recentQuestions = $this->repository->recentQuestions($accountId);
        for ($generation = 0; $generation < 5; $generation++) {
            $question = $this->gemini->generateQuestion($session, $recentQuestions);
            $fingerprint = SkillTrainingRepository::questionFingerprint($question['question']);
            if ($this->repository->questionWasAsked($accountId, $fingerprint)) {
                $recentQuestions[] = $question['question'];
                continue;
            }
            try {
                return $this->repository->createAttempt($accountId, $sessionId, $question);
            } catch (PDOException $error) {
                if ((string) $error->getCode() !== '23000') {
                    throw $error;
                }
                $recentQuestions[] = $question['question'];
            }
        }
        throw new RuntimeException('Could not generate a fresh question after several attempts. Please try again.');
    }

    public function submit(int $accountId, int $sessionId, int $attemptId, string $answer): array
    {
        $answer = trim($answer);
        if ($answer === '' || mb_strlen($answer) > 10000) {
            throw new RuntimeException('Enter an answer before submitting.');
        }
        $attempt = $this->repository->submitAnswer($accountId, $sessionId, $attemptId, $answer);
        if ($attempt === null) {
            throw new RuntimeException('This answer is no longer available.');
        }
        return $this->evaluate($accountId, $attemptId, $attempt);
    }

    public function dispatch(int $accountId, int $sessionId, int $attemptId): array
    {
        $attempt = $this->repository->dispatchAttempt($accountId, $sessionId, $attemptId);
        if ($attempt === null) {
            throw new RuntimeException('This question can no longer be dispatched.');
        }
        return $attempt;
    }

    public function retryEvaluation(int $accountId, int $attemptId): array
    {
        $attempt = $this->repository->attemptForEvaluation($accountId, $attemptId);
        if ($attempt === null) {
            throw new RuntimeException('This answer cannot be retried.');
        }
        return $this->evaluate($accountId, $attemptId, $attempt);
    }

    private function evaluate(int $accountId, int $attemptId, array $attempt): array
    {
        try {
            $session = $this->repository->sessionById($accountId, (int) $attempt['session_id']);
            $evaluation = $this->gemini->evaluate(
                $attempt['question'],
                $attempt['expected_answer'],
                $attempt['answer_text'],
                (string) ($session['training_language'] ?? 'en')
            );
        } catch (\Throwable $error) {
            $this->repository->markEvaluationFailed($accountId, $attemptId);
            throw new RuntimeException('Your answer was saved, but Gemini could not evaluate it. Retry the evaluation.', 0, $error);
        }
        $this->repository->storeEvaluation($accountId, $attemptId, $evaluation);
        $nextSession = $this->repository->rescheduleAfterAnswer($accountId, (int) $attempt['session_id']);
        return [
            'attempt' => $this->repository->attemptById($accountId, $attemptId),
            'next' => $nextSession !== null,
            'next_session' => $nextSession,
        ];
    }

    public function timeout(int $accountId, int $sessionId, int $attemptId): void
    {
        $this->repository->unanswered($accountId, $sessionId, $attemptId);
    }

    public function stop(int $accountId, int $sessionId): void
    {
        $this->repository->stopSession($accountId, $sessionId);
    }

    public function heartbeat(int $accountId, int $sessionId): bool
    {
        return $this->repository->heartbeat($accountId, $sessionId);
    }
}
