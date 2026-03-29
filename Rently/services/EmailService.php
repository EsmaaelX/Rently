<?php
/**
 * EmailService
 * Sends emails using PHPMailer via real SMTP.
 * Also logs all emails to file for development debugging.
 */

require_once __DIR__ . '/../vendor/phpmailer/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private string $fromName = 'Rently';
    private string $fromEmail = 'noreply@rently.com';
    private string $logFile;

    public function __construct()
    {
        $this->logFile = __DIR__ . '/../uploads/email_log.txt';
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Send an email via PHPMailer.
     */
    public function send(string $to, string $subject, string $htmlBody): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp-relay.brevo.com'; // Brevo SMTP server address
            $mail->SMTPAuth   = true;
            
            // --------------------------------------------------------------------------------------------
            // TODO: PUT YOUR REAL BREVO/SENDGRID USERNAME AND PASSWORD HERE:
            // --------------------------------------------------------------------------------------------
            $mail->Username   = 'YOUR_BREVO_EMAIL@example.com'; // Usually the email you registered with
            $mail->Password   = 'YOUR_BREVO_SMTP_KEY';          // The SMTP password/key they provide you
            // --------------------------------------------------------------------------------------------
            
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;

            $mail->send();
            $sent = true;
        } catch (Exception $e) {
            $sent = false;
        }

        // Always log for debugging
        $this->log($to, $subject, $htmlBody, $sent);

        return $sent;
    }

    /**
     * Send a verification code email.
     */
    public function sendVerificationCode(string $to, string $code, string $fullName): bool
    {
        $subject = 'Rently — Verify Your Email';
        $body = $this->buildTemplate(
            'Verify Your Email',
            "Hi {$fullName},",
            "Thank you for signing up for Rently! Please use the verification code below to activate your account.",
            $code,
            'This code expires in 15 minutes. If you did not create an account, please ignore this email.'
        );
        return $this->send($to, $subject, $body);
    }

    /**
     * Send a 2FA code email.
     */
    public function sendTwoFACode(string $to, string $code, string $fullName): bool
    {
        $subject = 'Rently — Login Verification Code';
        $body = $this->buildTemplate(
            'Login Verification',
            "Hi {$fullName},",
            "A login attempt was detected on your account. Use the code below to verify your identity.",
            $code,
            'This code expires in 10 minutes. If you did not attempt to log in, please change your password immediately.'
        );
        return $this->send($to, $subject, $body);
    }

    /**
     * Send a general notification email.
     */
    public function sendNotification(string $to, string $fullName, string $title, string $message): bool
    {
        $subject = "Rently — {$title}";
        $body = $this->buildTemplate(
            $title,
            "Hi {$fullName},",
            $message,
            null,
            'Visit your Rently dashboard for more details.'
        );
        return $this->send($to, $subject, $body);
    }

    /**
     * Build a styled HTML email template.
     */
    private function buildTemplate(
        string $heading,
        string $greeting,
        string $bodyText,
        ?string $code = null,
        string $footer = ''
    ): string {
        $codeBlock = '';
        if ($code) {
            $codeBlock = "
            <div style='text-align:center; margin: 30px 0;'>
                <div style='display:inline-block; background: linear-gradient(135deg, #6C5CE7, #5A4BD1);
                            color: #fff; font-size: 32px; font-weight: 700; letter-spacing: 8px;
                            padding: 16px 40px; border-radius: 12px; font-family: monospace;'>
                    {$code}
                </div>
            </div>";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head><meta charset='UTF-8'></head>
        <body style='margin:0; padding:0; background:#f4f4f8; font-family: Inter, Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f4f8; padding: 40px 0;'>
                <tr><td align='center'>
                    <table width='560' cellpadding='0' cellspacing='0'
                           style='background:#fff; border-radius:16px; overflow:hidden;
                                  box-shadow: 0 4px 24px rgba(0,0,0,0.08);'>
                        <!-- Header -->
                        <tr>
                            <td style='background: linear-gradient(135deg, #2D3436 0%, #1a1a2e 100%);
                                       padding: 30px; text-align:center;'>
                                <h1 style='color:#fff; margin:0; font-size:24px;'>
                                    🏠 Rently
                                </h1>
                            </td>
                        </tr>
                        <!-- Body -->
                        <tr>
                            <td style='padding: 40px 35px;'>
                                <h2 style='color:#2D3436; font-size:22px; margin:0 0 8px;'>{$heading}</h2>
                                <p style='color:#636E72; font-size:15px; margin:0 0 20px;'>{$greeting}</p>
                                <p style='color:#2D3436; font-size:15px; line-height:1.6; margin:0 0 10px;'>
                                    {$bodyText}
                                </p>
                                {$codeBlock}
                                <p style='color:#636E72; font-size:13px; margin:20px 0 0; line-height:1.5;'>
                                    {$footer}
                                </p>
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td style='background:#f8f9fa; padding:20px 35px; text-align:center;
                                       border-top:1px solid #eee;'>
                                <p style='color:#aaa; font-size:12px; margin:0;'>
                                    &copy; " . date('Y') . " Rently. All rights reserved.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td></tr>
            </table>
        </body>
        </html>";
    }

    /**
     * Log email to file for debugging.
     */
    private function log(string $to, string $subject, string $body, bool $sent): void
    {
        $status = $sent ? 'SENT' : 'FAILED';
        $timestamp = date('Y-m-d H:i:s');

        // Extract code from body if present
        $code = '';
        if (preg_match('/letter-spacing:\s*8px[^>]*>\s*(\d{6})\s*</', $body, $m)) {
            $code = " | CODE: {$m[1]}";
        }

        $entry = "[{$timestamp}] [{$status}] To: {$to} | Subject: {$subject}{$code}\n";
        file_put_contents($this->logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
