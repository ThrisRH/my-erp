ALTER TABLE skill_training_sessions
    ADD COLUMN training_language ENUM('en', 'vi') NOT NULL DEFAULT 'en' AFTER skill_level;
