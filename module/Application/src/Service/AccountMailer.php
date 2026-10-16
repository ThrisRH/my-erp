<?php

declare(strict_types=1);

namespace Application\Service;

use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

class AccountMailer
{
    public function available(): bool
    {
        if (getenv('MAIL_TRANSPORT') === 'outbox') {
            return getenv('APP_ENV') !== 'production';
        }
        return getenv('MAIL_TRANSPORT') === 'smtp'
            && (string) getenv('SMTP_HOST') !== ''
            && (string) getenv('SMTP_USERNAME') !== ''
            && (string) getenv('SMTP_PASSWORD') !== ''
            && filter_var(getenv('MAIL_FROM'), FILTER_VALIDATE_EMAIL) !== false
            && in_array(getenv('SMTP_ENCRYPTION'), ['starttls', 'smtps'], true);
    }

    public function sendLink(string $email, string $purpose, string $token): void
    {
        if (!$this->available()) {
            throw new RuntimeException('Email delivery is not configured.');
        }
        $baseUrl = rtrim((string) (getenv('APP_BASE_URL') ?: 'http://localhost:8000'), '/');
        $route = $purpose === 'verify_email' ? '/verify-email' : '/reset-password';
        $url = $baseUrl . $route . '?token=' . rawurlencode($token);
        $subject = $purpose === 'verify_email' ? 'Verify your My ERP email' : 'Reset your My ERP password';
        $body = $purpose === 'verify_email'
            ? "Open this link within 24 hours to verify your email:\n$url\n\nIf you did not sign up, ignore this message."
            : "Open this link within 30 minutes to reset your password:\n$url\n\nIf you did not request a reset, ignore this message.";
        if (getenv('MAIL_TRANSPORT') === 'outbox') {
            $directory = sys_get_temp_dir() . '/my-erp-mail-outbox';
            if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new RuntimeException('Cannot create the local mail outbox.');
            }
            $file = $directory . '/' . bin2hex(random_bytes(12)) . '.txt';
            if (file_put_contents($file, "To: $email\nSubject: $subject\n\n$body\n", LOCK_EX) === false) {
                throw new RuntimeException('Cannot write the local mail outbox.');
            }
            chmod($file, 0600);
            return;
        }
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = (string) getenv('SMTP_HOST');
            $mail->Port = (int) (getenv('SMTP_PORT') ?: 587);
            $mail->SMTPAuth = true;
            $mail->Username = (string) getenv('SMTP_USERNAME');
            $mail->Password = (string) getenv('SMTP_PASSWORD');
            $mail->SMTPSecure = getenv('SMTP_ENCRYPTION') === 'smtps'
                ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom((string) getenv('MAIL_FROM'), 'My ERP');
            $mail->addAddress($email);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
        } catch (\Throwable $error) {
            throw new RuntimeException('Email delivery failed.');
        }
    }
}
