<?php

declare(strict_types=1);

namespace SkillTraining\Service;

use RuntimeException;

class GeminiClient
{
    private string $apiKey;
    private string $model;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = trim($apiKey ?? (string) getenv('GEMINI_API_KEY'));
        $this->model = trim($model ?? (string) (getenv('GEMINI_MODEL') ?: 'gemini-3.8-flash'));
    }

    public function generateQuestion(array $session, array $recentQuestions): array
    {
        $language = ($session['training_language'] ?? 'en') === 'vi' ? 'Vietnamese' : 'English';
        $keywords = preg_split('/[,;\r\n]+/u', (string) $session['keywords'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $keywords = array_values(array_filter(array_map('trim', $keywords), static fn(string $keyword): bool => $keyword !== ''));
        if (count($keywords) > 1) {
            shuffle($keywords);
        }
        $prompt = sprintf(
            "You are a precise SQL tutor. Create exactly one short MySQL question for a timed skill drill.\n"
            . "Write the question and expected answer in %s. Skill level: %s. Learner keywords: %s.\n"
            . "Choose one topic unpredictably from the supplied keywords; do not follow their listed order. If a keyword is broad, randomly choose a foundational subtopic.\n"
            . "Choose exactly one question_type: theory OR exercise. Ask one foundational concept or one focused task only.\n"
            . "For every exercise, write all table and column identifiers in English snake_case (for example employee, employee_id, department_name). Never use localized or transliterated identifiers such as nhan_vien. Keep identifier names in English even when the question language is Vietnamese.\n"
            . "For an exercise, include the required table/column/value details and change names, values, and framing.\n"
            . "Never repeat a prior question verbatim. Concepts may recur, but use a meaningfully different question wording and scenario. Do not include the solution in the question.\n"
            . "Recent questions from this account to avoid repeating: %s\n"
            . "Return only a JSON object with question_type (theory|exercise), question, expected_answer. Keep question <= 500 characters and expected_answer <= 1500 characters.",
            $language,
            $session['skill_level'],
            implode('; ', $keywords),
            json_encode($recentQuestions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $data = $this->generate($prompt);
        if (!in_array($data['question_type'] ?? null, ['theory', 'exercise'], true)
            || !is_string($data['question'] ?? null) || trim($data['question']) === ''
            || mb_strlen($data['question']) > 500
            || !is_string($data['expected_answer'] ?? null) || trim($data['expected_answer']) === ''
            || mb_strlen($data['expected_answer']) > 1500) {
            throw new RuntimeException('Gemini returned an invalid question.');
        }
        return [
            'question_type' => $data['question_type'],
            'question' => trim($data['question']),
            'expected_answer' => trim($data['expected_answer']),
        ];
    }

    public function evaluate(string $question, string $expectedAnswer, string $answer, string $language = 'en'): array
    {
        $responseLanguage = $language === 'vi' ? 'Vietnamese' : 'English';
        $prompt = sprintf(
            "You are a fair SQL tutor evaluating one learner answer.\n"
            . "Write the verdict and explanation in %s. The learner answer may be in any language.\n"
            . "Question: %s\nExpected answer and rubric: %s\nLearner answer: %s\n"
            . "Grade the learner's SQL logic and intent, not capitalization style. SQL keywords are case-insensitive for grading: do not deduct points because SELECT, FROM, WHERE, JOIN, GROUP BY, or similar keywords use upper, lower, or mixed case. Also do not require formatting or an optional trailing semicolon. Deduct points only for meaningful logic or syntax errors that change whether the answer satisfies the task.\n"
            . "Score correctness from 0 to 100. Give a short verdict. Explain deeply only if the answer reveals a misunderstanding; otherwise keep explanation concise. Do not execute SQL.\n"
            . "Return only JSON with score (integer 0..100), verdict (<= 300 chars), explanation (<= 2000 chars).",
            $responseLanguage,
            $question,
            $expectedAnswer,
            $answer
        );
        $data = $this->generate($prompt);
        $score = filter_var($data['score'] ?? null, FILTER_VALIDATE_INT);
        if ($score === false || $score < 0 || $score > 100
            || !is_string($data['verdict'] ?? null) || mb_strlen($data['verdict']) > 300
            || !is_string($data['explanation'] ?? null) || mb_strlen($data['explanation']) > 2000) {
            throw new RuntimeException('Gemini returned an invalid evaluation.');
        }
        return [
            'score' => $score,
            'verdict' => trim($data['verdict']),
            'explanation' => trim($data['explanation']),
        ];
    }

    private function generate(string $prompt): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('Gemini API key is not configured in the application environment.');
        }
        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.7,
                'maxOutputTokens' => 700,
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\nX-goog-api-key: {$this->apiKey}\r\n",
                'content' => $payload,
                'timeout' => 25,
                'ignore_errors' => true,
            ],
        ]);
        $models = array_values(array_unique([
            $this->model,
            'gemini-3.5-flash-lite',
            'gemini-3.6-flash',
        ]));
        $lastFailure = 'Gemini could not process this request.';

        foreach ($models as $model) {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
                . rawurlencode($model) . ':generateContent';
            unset($http_response_header);
            $response = @file_get_contents($url, false, $context);
            $statusLine = $http_response_header[0] ?? '';
            preg_match('/\s(\d{3})\s/', $statusLine, $statusMatch);
            $statusCode = isset($statusMatch[1]) ? (int) $statusMatch[1] : 0;

            if ($response === false || $statusCode < 200 || $statusCode >= 300) {
                $decoded = json_decode((string) $response, true);
                $providerMessage = $decoded['error']['message'] ?? null;
                if ($statusCode === 0) {
                    $transportMessage = error_get_last()['message'] ?? 'No HTTP response was received.';
                    $lastFailure = sprintf('Gemini connection failed: %s', $transportMessage);
                } else {
                    $lastFailure = $providerMessage !== null
                        ? sprintf('Gemini request failed (HTTP %d): %s', $statusCode, $providerMessage)
                        : sprintf('Gemini request failed (HTTP %d).', $statusCode);
                }
                if (in_array($statusCode, [0, 429, 503], true) && $model !== end($models)) {
                    continue;
                }
                throw new RuntimeException($lastFailure);
            }

            $decoded = json_decode($response, true);
            $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if (!is_string($text)) {
                throw new RuntimeException('Gemini returned no usable content.');
            }
            $data = json_decode($text, true);
            if (!is_array($data)) {
                throw new RuntimeException('Gemini returned invalid JSON.');
            }
            return $data;
        }

        throw new RuntimeException($lastFailure);
    }
}
