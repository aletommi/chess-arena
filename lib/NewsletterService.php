<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/UserRepository.php';

final class NewsletterService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Invia una newsletter via email a tutti gli iscritti attivi
    public function send(int $senderId, string $subject, string $body): int
    {
        // 1. Log the message
        $stmt = $this->pdo->prepare(
            'INSERT INTO newsletter_messages (sent_by, subject, body, sent_at) VALUES (:sent_by, :subject, :body, NOW())'
        );
        $stmt->execute([
            'sent_by' => $senderId,
            'subject' => $subject,
            'body' => $body,
        ]);

        // 2. Get recipients (Active users who opted in)
        $userRepo = new UserRepository($this->pdo);
        $recipients = $userRepo->listNewsletterSubscribers();

        if (empty($recipients)) {
            return 0;
        }

        // 3. Send via PHPMailer
        $mail = new PHPMailer(true);
        $count = 0;

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;

            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);

            // Optimization: Keep Alive to reuse connection
            $mail->SMTPKeepAlive = true;

            foreach ($recipients as $user) {
                try {
                    $mail->addAddress($user['email'], $user['username']);
                    $mail->send();
                    $mail->clearAddresses();
                    $count++;
                } catch (Exception $e) {
                    error_log("Mailer Error ({$user['email']}): " . $mail->ErrorInfo);
                    // Reset connection if broken
                    $mail->getSMTPInstance()->reset();
                }
            }

            $mail->smtpClose();

        } catch (Exception $e) {
            error_log("PHPMailer Configuration Error: " . $e->getMessage());
        }

        return $count;
    }

    // Elenca lo storico dei messaggi newsletter inviati
    public function listMessages(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.*, u.email as admin_email 
             FROM newsletter_messages m 
             JOIN users u ON m.sent_by = u.id 
             ORDER BY m.sent_at DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
