<?php

declare(strict_types=1);

namespace SkillTraining\Model;

use PDO;

class SkillTrainingRepository
{
    private PDO $connection;

    public function __construct(array $database)
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $database['host'] ?? '127.0.0.1',
            (int) ($database['port'] ?? 3306),
            $database['database'] ?? 'erp'
        );
        $this->connection = new PDO($dsn, (string) ($database['username'] ?? ''), (string) ($database['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function dashboard(int $accountId): array
    {
        $stats = $this->connection->prepare(
            "SELECT COUNT(*) AS attempts_today,
                    SUM(CASE WHEN status = 'evaluated' AND score >= 80 THEN 1 ELSE 0 END) AS correct_today,
                    SUM(CASE WHEN status = 'evaluated' THEN 1 ELSE 0 END) AS evaluated_today,
                    AVG(CASE WHEN status IN ('answered', 'evaluation_failed', 'evaluated') THEN response_seconds END) AS average_seconds_today
             FROM skill_training_attempts
             WHERE account_id = :account_id AND DATE(generated_at) = CURRENT_DATE()"
        );
        $stats->execute(['account_id' => $accountId]);
        $today = $stats->fetch() ?: [];

        $previous = $this->connection->prepare(
            'SELECT COUNT(*) FROM skill_training_attempts
             WHERE account_id = :account_id AND DATE(generated_at) = CURRENT_DATE() - INTERVAL 1 DAY'
        );
        $previous->execute(['account_id' => $accountId]);
        $previousCount = (int) $previous->fetchColumn();
        $todayCount = (int) ($today['attempts_today'] ?? 0);
        $today['daily_change_percent'] = $previousCount === 0
            ? null : round((($todayCount - $previousCount) / $previousCount) * 100, 1);
        $today['accuracy_today'] = (int) ($today['evaluated_today'] ?? 0) === 0
            ? null : round(((int) $today['correct_today'] / (int) $today['evaluated_today']) * 100, 1);

        $overall = $this->connection->prepare(
            "SELECT SUM(CASE WHEN status = 'evaluated' AND score >= 80 THEN 1 ELSE 0 END) AS correct_count,
                    SUM(CASE WHEN status = 'evaluated' THEN 1 ELSE 0 END) AS evaluated_count,
                    AVG(CASE WHEN status IN ('answered', 'evaluation_failed', 'evaluated') THEN response_seconds END) AS average_seconds
             FROM skill_training_attempts
             WHERE account_id = :account_id"
        );
        $overall->execute(['account_id' => $accountId]);
        $overallStats = $overall->fetch() ?: [];
        $overallStats['accuracy'] = (int) ($overallStats['evaluated_count'] ?? 0) === 0
            ? null : round(((int) $overallStats['correct_count'] / (int) $overallStats['evaluated_count']) * 100, 1);

        $history = $this->connection->prepare(
            'SELECT id, question_type, question, answer_text, response_seconds, is_late, status, score,
                    verdict, explanation, generated_at, submitted_at
             FROM skill_training_attempts
             WHERE account_id = :account_id
             ORDER BY id DESC LIMIT 20'
        );
        $history->execute(['account_id' => $accountId]);

        $daily = $this->connection->prepare(
            "SELECT DATE(generated_at) AS training_date, COUNT(*) AS attempts,
                SUM(CASE WHEN status = 'evaluated' AND score >= 80 THEN 1 ELSE 0 END) AS correct_count,
                SUM(CASE WHEN status = 'evaluated' THEN 1 ELSE 0 END) AS evaluated_count,
                AVG(CASE WHEN submitted_at IS NOT NULL THEN response_seconds END) AS average_seconds
             FROM skill_training_attempts
             WHERE account_id = :account_id AND generated_at >= CURRENT_DATE() - INTERVAL 13 DAY
             GROUP BY DATE(generated_at) ORDER BY training_date ASC"
        );
        $daily->execute(['account_id' => $accountId]);

        $session = $this->activeSession($accountId);
        return [
            'today' => $today,
            'overall' => $overallStats,
            'daily' => $daily->fetchAll(),
            'history' => $history->fetchAll(),
            'activeSession' => $session,
            'activeAttempt' => $session === null ? null : $this->activeAttempt($accountId, (int) $session['id']),
        ];
    }

    public function activeSession(int $accountId): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT id, keywords, skill_level, training_language, min_interval_minutes, max_interval_minutes,
                    answer_duration_seconds, status, UNIX_TIMESTAMP(next_question_at) AS next_question_at,
                    UNIX_TIMESTAMP(started_at) AS started_at
             FROM skill_training_sessions
             WHERE account_id = :account_id AND status = 'active'
             ORDER BY id DESC LIMIT 1"
        );
        $statement->execute(['account_id' => $accountId]);
        $session = $statement->fetch();
        if (!$session) {
            return null;
        }
        $session['id'] = (int) $session['id'];
        return $session;
    }

    public function activeAttempt(int $accountId, int $sessionId): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT id, question_type, question, answer_text, status, is_late,
                    response_seconds, UNIX_TIMESTAMP(generated_at) AS generated_at,
                    UNIX_TIMESTAMP(deadline_at) AS deadline_at
             FROM skill_training_attempts
             WHERE account_id = :account_id AND session_id = :session_id
               AND status IN ('awaiting_answer', 'evaluation_failed')
             ORDER BY id DESC LIMIT 1"
        );
        $statement->execute(['account_id' => $accountId, 'session_id' => $sessionId]);
        $attempt = $statement->fetch();
        return $attempt ?: null;
    }

    public function startSession(int $accountId, array $settings): array
    {
        $this->connection->beginTransaction();
        try {
                        $abandon = $this->connection->prepare(
                                "UPDATE skill_training_attempts AS attempt
                                 JOIN skill_training_sessions AS session
                                     ON session.id = attempt.session_id AND session.account_id = attempt.account_id
                                 SET attempt.status = 'unanswered', attempt.submitted_at = NOW(),
                                         attempt.response_seconds = TIMESTAMPDIFF(SECOND, attempt.generated_at, NOW())
                                 WHERE session.account_id = :account_id AND session.status = 'active'
                                     AND attempt.status = 'awaiting_answer'"
                        );
                        $abandon->execute(['account_id' => $accountId]);
                        $stop = $this->connection->prepare(
                "UPDATE skill_training_sessions SET status = 'stopped', ended_at = NOW()
                 WHERE account_id = :account_id AND status = 'active'"
            );
            $stop->execute(['account_id' => $accountId]);
            $nextAt = (int) $settings['min_interval_minutes'] * 60;
            $nextAt += random_int(0, ((int) $settings['max_interval_minutes'] - (int) $settings['min_interval_minutes']) * 60);
            $insert = $this->connection->prepare(
                'INSERT INTO skill_training_sessions
                    (account_id, keywords, skill_level, training_language, min_interval_minutes, max_interval_minutes,
                     answer_duration_seconds, next_question_at)
                 VALUES (:account_id, :keywords, :skill_level, :training_language, :min_interval_minutes, :max_interval_minutes,
                     :answer_duration_seconds, DATE_ADD(NOW(), INTERVAL :next_seconds SECOND))'
            );
            $insert->execute([
                'account_id' => $accountId,
                'keywords' => $settings['keywords'],
                'skill_level' => $settings['skill_level'],
                'training_language' => $settings['training_language'],
                'min_interval_minutes' => $settings['min_interval_minutes'],
                'max_interval_minutes' => $settings['max_interval_minutes'],
                'answer_duration_seconds' => $settings['answer_duration_seconds'],
                'next_seconds' => $nextAt,
            ]);
            $id = (int) $this->connection->lastInsertId();
            $this->connection->commit();

        } catch (\Throwable $error) {
            $this->connection->rollBack();
            throw $error;
        }
        return $this->sessionById($accountId, $id);
    }

    public function sessionById(int $accountId, int $sessionId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, keywords, skill_level, training_language, min_interval_minutes, max_interval_minutes,
                    answer_duration_seconds, status, UNIX_TIMESTAMP(next_question_at) AS next_question_at
             FROM skill_training_sessions WHERE id = :session_id AND account_id = :account_id LIMIT 1'
        );
        $statement->execute(['session_id' => $sessionId, 'account_id' => $accountId]);
        return $statement->fetch() ?: null;
    }

    public function dispatchAttempt(int $accountId, int $sessionId, int $attemptId): ?array
    {
        $statement = $this->connection->prepare(
            "UPDATE skill_training_attempts AS attempt
             JOIN skill_training_sessions AS session
               ON session.id = attempt.session_id AND session.account_id = attempt.account_id
             SET attempt.generated_at = NOW(),
                 attempt.deadline_at = DATE_ADD(NOW(), INTERVAL session.answer_duration_seconds SECOND),
                 session.heartbeat_at = NOW()
             WHERE attempt.id = :attempt_id AND attempt.session_id = :session_id
               AND attempt.account_id = :account_id AND attempt.status = 'awaiting_answer'
               AND session.status = 'active'"
        );
        $statement->execute([
            'attempt_id' => $attemptId,
            'session_id' => $sessionId,
            'account_id' => $accountId,
        ]);
        $attempt = $this->attemptById($accountId, $attemptId);
        return $attempt && $attempt['status'] === 'awaiting_answer' ? $attempt : null;
    }

    public static function questionFingerprint(string $question): string
    {
        $normalized = mb_strtolower(preg_replace('/[\p{P}\s]+/u', '', trim($question)) ?? $question, 'UTF-8');
        return hash('sha256', $normalized);
    }

    public function recentQuestions(int $accountId): array
    {
        $statement = $this->connection->prepare(
            'SELECT question FROM skill_training_attempts
             WHERE account_id = :account_id
             ORDER BY id DESC LIMIT 50'
        );
        $statement->execute(['account_id' => $accountId]);
        return array_column($statement->fetchAll(), 'question');
    }

    public function questionWasAsked(int $accountId, string $fingerprint): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM skill_training_attempts
             WHERE account_id = :account_id AND question_fingerprint = :fingerprint LIMIT 1'
        );
        $statement->execute(['account_id' => $accountId, 'fingerprint' => $fingerprint]);
        return $statement->fetchColumn() !== false;
    }

    public function createAttempt(int $accountId, int $sessionId, array $question): array
    {
        $this->connection->beginTransaction();
        try {
            $lock = $this->connection->prepare(
                "SELECT id, answer_duration_seconds FROM skill_training_sessions
                 WHERE id = :session_id AND account_id = :account_id AND status = 'active'
                   AND next_question_at <= NOW() FOR UPDATE"
            );
            $lock->execute(['session_id' => $sessionId, 'account_id' => $accountId]);
            $session = $lock->fetch();
            if (!$session) {
                throw new \RuntimeException('The next question is not due or the session has ended.');
            }
            $pending = $this->connection->prepare(
                "SELECT COUNT(*) FROM skill_training_attempts
                 WHERE session_id = :session_id AND account_id = :account_id
                   AND status IN ('awaiting_answer', 'answered', 'evaluation_failed')"
            );
            $pending->execute(['session_id' => $sessionId, 'account_id' => $accountId]);
            if ((int) $pending->fetchColumn() > 0) {
                throw new \RuntimeException('Submit or retry the current answer before requesting another question.');
            }
            $insert = $this->connection->prepare(
                "INSERT INTO skill_training_attempts
                          (session_id, account_id, question_type, question, question_fingerprint, expected_answer, deadline_at)
                      VALUES (:session_id, :account_id, :question_type, :question, :question_fingerprint, :expected_answer,
                    DATE_ADD(NOW(), INTERVAL :duration SECOND))"
            );
            $insert->execute([
                'session_id' => $sessionId,
                'account_id' => $accountId,
                'question_type' => $question['question_type'],
                'question' => $question['question'],
                'question_fingerprint' => self::questionFingerprint($question['question']),
                'expected_answer' => $question['expected_answer'],
                'duration' => (int) $session['answer_duration_seconds'],
            ]);
            $attemptId = (int) $this->connection->lastInsertId();
            $this->connection->prepare(
                'UPDATE skill_training_sessions SET heartbeat_at = NOW() WHERE id = :session_id AND account_id = :account_id'
            )->execute(['session_id' => $sessionId, 'account_id' => $accountId]);
            $this->connection->commit();
        } catch (\Throwable $error) {
            $this->connection->rollBack();
            throw $error;
        }
        return $this->attemptById($accountId, $attemptId);
    }

    public function attemptById(int $accountId, int $attemptId): ?array
    {
        $statement = $this->connection->prepare(
                'SELECT id, session_id, question_type, question, expected_answer, answer_text, status, is_late,
                    score, verdict, explanation,
                    UNIX_TIMESTAMP(generated_at) AS generated_at, UNIX_TIMESTAMP(deadline_at) AS deadline_at
             FROM skill_training_attempts WHERE id = :attempt_id AND account_id = :account_id LIMIT 1'
        );
        $statement->execute(['attempt_id' => $attemptId, 'account_id' => $accountId]);
        return $statement->fetch() ?: null;
    }

    public function attemptForEvaluation(int $accountId, int $attemptId): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT id, session_id, question, expected_answer, answer_text, status
             FROM skill_training_attempts
             WHERE id = :attempt_id AND account_id = :account_id
               AND status = 'evaluation_failed' AND answer_text IS NOT NULL LIMIT 1"
        );
        $statement->execute(['attempt_id' => $attemptId, 'account_id' => $accountId]);
        return $statement->fetch() ?: null;
    }

    public function submitAnswer(int $accountId, int $sessionId, int $attemptId, string $answer): ?array
    {
        $this->connection->beginTransaction();
        try {
            $statement = $this->connection->prepare(
                "SELECT id, UNIX_TIMESTAMP(generated_at) AS generated_at,
                        UNIX_TIMESTAMP(deadline_at) AS deadline_at, status
                 FROM skill_training_attempts
                 WHERE id = :attempt_id AND session_id = :session_id AND account_id = :account_id FOR UPDATE"
            );
            $statement->execute(['attempt_id' => $attemptId, 'session_id' => $sessionId, 'account_id' => $accountId]);
            $attempt = $statement->fetch();
            if (!$attempt || $attempt['status'] !== 'awaiting_answer') {
                throw new \RuntimeException('This answer is no longer accepting submissions.');
            }
            $elapsed = max(0, time() - (int) $attempt['generated_at']);
            $update = $this->connection->prepare(
                "UPDATE skill_training_attempts SET answer_text = :answer_text, response_seconds = :response_seconds,
                    is_late = :is_late, status = 'answered', submitted_at = NOW()
                 WHERE id = :attempt_id AND account_id = :account_id"
            );
            $update->execute([
                'answer_text' => $answer,
                'response_seconds' => $elapsed,
                'is_late' => (int) ($elapsed > max(0, (int) $attempt['deadline_at'] - (int) $attempt['generated_at'])),
                'attempt_id' => $attemptId,
                'account_id' => $accountId,
            ]);
            $session = $this->connection->prepare(
                'UPDATE skill_training_sessions SET heartbeat_at = NOW() WHERE id = :session_id AND account_id = :account_id AND status = \'active\''
            );
            $session->execute(['session_id' => $sessionId, 'account_id' => $accountId]);
            $this->connection->commit();
        } catch (\Throwable $error) {
            $this->connection->rollBack();
            throw $error;
        }
        return $this->attemptById($accountId, $attemptId);
    }

    public function unanswered(int $accountId, int $sessionId, int $attemptId): void
    {
        $this->connection->beginTransaction();
        try {
            $update = $this->connection->prepare(
                "UPDATE skill_training_attempts SET status = 'unanswered', is_late = 1, response_seconds =
                    (SELECT answer_duration_seconds FROM skill_training_sessions WHERE id = :lookup_session_id AND account_id = :lookup_account_id),
                    submitted_at = NOW()
                 WHERE id = :attempt_id AND session_id = :session_id AND account_id = :account_id AND status = 'awaiting_answer'"
            );
            $update->execute([
                'lookup_session_id' => $sessionId,
                'lookup_account_id' => $accountId,
                'attempt_id' => $attemptId,
                'session_id' => $sessionId,
                'account_id' => $accountId,
            ]);
            $stop = $this->connection->prepare(
                "UPDATE skill_training_sessions SET status = 'timed_out', ended_at = NOW(), heartbeat_at = NOW()
                 WHERE id = :session_id AND account_id = :account_id AND status = 'active'"
            );
            $stop->execute(['session_id' => $sessionId, 'account_id' => $accountId]);
            $this->connection->commit();
        } catch (\Throwable $error) {
            $this->connection->rollBack();
            throw $error;
        }
    }

    public function storeEvaluation(int $accountId, int $attemptId, array $evaluation): void
    {
        $statement = $this->connection->prepare(
            "UPDATE skill_training_attempts SET status = 'evaluated', score = :score, verdict = :verdict,
                    explanation = :explanation, evaluated_at = NOW()
             WHERE id = :attempt_id AND account_id = :account_id AND status IN ('answered', 'evaluation_failed')"
        );
        $statement->execute([
            'score' => $evaluation['score'],
            'verdict' => $evaluation['verdict'],
            'explanation' => $evaluation['explanation'],
            'attempt_id' => $attemptId,
            'account_id' => $accountId,
        ]);
    }

    public function markEvaluationFailed(int $accountId, int $attemptId): void
    {
        $statement = $this->connection->prepare(
            "UPDATE skill_training_attempts SET status = 'evaluation_failed'
             WHERE id = :attempt_id AND account_id = :account_id AND status = 'answered'"
        );
        $statement->execute(['attempt_id' => $attemptId, 'account_id' => $accountId]);
    }

    public function rescheduleAfterAnswer(int $accountId, int $sessionId): ?array
    {
        $session = $this->sessionById($accountId, $sessionId);
        if (!$session || $session['status'] !== 'active') {
            return null;
        }
        $delay = random_int((int) $session['min_interval_minutes'] * 60, (int) $session['max_interval_minutes'] * 60);
        $statement = $this->connection->prepare(
            'UPDATE skill_training_sessions SET next_question_at = DATE_ADD(NOW(), INTERVAL :delay SECOND), heartbeat_at = NOW()
             WHERE id = :session_id AND account_id = :account_id AND status = \'active\''
        );
        $statement->execute(['delay' => $delay, 'session_id' => $sessionId, 'account_id' => $accountId]);
        return $this->sessionById($accountId, $sessionId);
    }

    public function heartbeat(int $accountId, int $sessionId): bool
    {
        $statement = $this->connection->prepare(
            "UPDATE skill_training_sessions SET heartbeat_at = NOW()
             WHERE id = :session_id AND account_id = :account_id AND status = 'active'"
        );
        $statement->execute(['session_id' => $sessionId, 'account_id' => $accountId]);
        return $statement->rowCount() > 0;
    }

    public function stopSession(int $accountId, int $sessionId, string $status = 'stopped'): void
    {
        $abandon = $this->connection->prepare(
            "UPDATE skill_training_attempts SET status = 'unanswered', submitted_at = NOW(),
                    response_seconds = TIMESTAMPDIFF(SECOND, generated_at, NOW())
             WHERE session_id = :session_id AND account_id = :account_id AND status = 'awaiting_answer'"
        );
        $abandon->execute(['session_id' => $sessionId, 'account_id' => $accountId]);
        $statement = $this->connection->prepare(
            "UPDATE skill_training_sessions SET status = :status, ended_at = NOW(), heartbeat_at = NOW()
             WHERE id = :session_id AND account_id = :account_id AND status = 'active'"
        );
        $statement->execute(['status' => $status, 'session_id' => $sessionId, 'account_id' => $accountId]);
    }

    public function stopStaleSessions(int $accountId): void
    {
                $abandon = $this->connection->prepare(
                        "UPDATE skill_training_attempts AS attempt
                         JOIN skill_training_sessions AS session
                             ON session.id = attempt.session_id AND session.account_id = attempt.account_id
                         SET attempt.status = 'unanswered', attempt.submitted_at = NOW(),
                                 attempt.response_seconds = TIMESTAMPDIFF(SECOND, attempt.generated_at, NOW())
                         WHERE session.account_id = :account_id AND session.status = 'active'
                             AND session.heartbeat_at < NOW() - INTERVAL 90 SECOND
                             AND attempt.status = 'awaiting_answer'"
                );
                $abandon->execute(['account_id' => $accountId]);
        $statement = $this->connection->prepare(
            "UPDATE skill_training_sessions SET status = 'stopped', ended_at = NOW()
             WHERE account_id = :account_id AND status = 'active'
               AND heartbeat_at < NOW() - INTERVAL 90 SECOND"
        );
        $statement->execute(['account_id' => $accountId]);
    }
}
