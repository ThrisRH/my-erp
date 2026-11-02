ALTER TABLE skill_training_attempts
    ADD COLUMN question_fingerprint CHAR(64) NULL AFTER question;

UPDATE skill_training_attempts AS attempt
JOIN (
    SELECT id, account_id, fingerprint,
           ROW_NUMBER() OVER (PARTITION BY account_id, fingerprint ORDER BY id DESC) AS duplicate_rank
    FROM (
        SELECT id, account_id,
               SHA2(LOWER(REGEXP_REPLACE(TRIM(question), '[[:space:]]+', ' ')), 256) AS fingerprint
        FROM skill_training_attempts
    ) AS normalized_questions
) AS ranked
    ON ranked.id = attempt.id AND ranked.account_id = attempt.account_id
SET attempt.question_fingerprint = CASE
    WHEN ranked.duplicate_rank = 1 THEN ranked.fingerprint
    ELSE NULL
END;

ALTER TABLE skill_training_attempts
    ADD UNIQUE KEY uq_skill_training_question_account (account_id, question_fingerprint);
