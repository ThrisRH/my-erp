CREATE TABLE skill_training_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    keywords VARCHAR(500) NOT NULL,
    skill_level ENUM('basic', 'advance') NOT NULL,
    min_interval_minutes SMALLINT UNSIGNED NOT NULL,
    max_interval_minutes SMALLINT UNSIGNED NOT NULL,
    answer_duration_seconds SMALLINT UNSIGNED NOT NULL,
    status ENUM('active', 'stopped', 'timed_out') NOT NULL DEFAULT 'active',
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    heartbeat_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    next_question_at DATETIME NOT NULL,
    ended_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_skill_training_session_account (id, account_id),
    KEY idx_skill_training_sessions_account_status (account_id, status, id),
    CONSTRAINT fk_skill_training_sessions_account
        FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE CASCADE,
    CONSTRAINT chk_skill_training_interval
        CHECK (min_interval_minutes >= 1 AND min_interval_minutes <= max_interval_minutes
            AND max_interval_minutes <= 120),
    CONSTRAINT chk_skill_training_duration
        CHECK (answer_duration_seconds BETWEEN 5 AND 600)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE skill_training_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    question_type ENUM('theory', 'exercise') NOT NULL,
    question TEXT NOT NULL,
    expected_answer TEXT NOT NULL,
    answer_text TEXT NULL,
    response_seconds INT UNSIGNED NULL,
    is_late TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('awaiting_answer', 'answered', 'unanswered', 'evaluation_failed', 'evaluated')
        NOT NULL DEFAULT 'awaiting_answer',
    score TINYINT UNSIGNED NULL,
    verdict VARCHAR(500) NULL,
    explanation TEXT NULL,
    generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deadline_at DATETIME NOT NULL,
    submitted_at DATETIME NULL,
    evaluated_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_skill_training_attempts_account_date (account_id, generated_at, status),
    KEY idx_skill_training_attempts_session (session_id, account_id, id),
    CONSTRAINT fk_skill_training_attempts_session
        FOREIGN KEY (session_id, account_id)
        REFERENCES skill_training_sessions (id, account_id) ON DELETE CASCADE,
    CONSTRAINT fk_skill_training_attempts_account
        FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE CASCADE,
    CONSTRAINT chk_skill_training_score CHECK (score IS NULL OR score BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE accounts
SET permissions = JSON_ARRAY_APPEND(COALESCE(permissions, JSON_ARRAY()), '$', 'skill-training.use')
WHERE is_admin = 0
    AND JSON_CONTAINS(COALESCE(permissions, JSON_ARRAY()), JSON_QUOTE('skill-training.use')) = 0;
