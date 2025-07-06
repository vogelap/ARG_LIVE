<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailManager {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        $this->mailer->Password   = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
        $this->mailer->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;
        //Recipients
        $from_email = defined('EMAIL_FROM') ? EMAIL_FROM : 'noreply@example.com';
        $from_name = defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'ARG Game';
        $this->mailer->setFrom($from_email, $from_name);
    }

    public function sendUsernameReminder($email, $username) {
        $this->mailer->addAddress($email);
        $this->mailer->isHTML(true);
        $this->mailer->Subject = 'Your ARG Game Username';
        $this->mailer->Body    = "Hello,<br><br>Your username for the game is: <b>$username</b><br><br>Good luck!";
        $this->mailer->send();
    }

    public function sendPasswordResetLink($email, $token) {
        $this->mailer->addAddress($email);
        $this->mailer->isHTML(true);
        $reset_link = SITE_URL . '/public/reset_password.php?token=' . $token;
        $this->mailer->Subject = 'ARG Game Password Reset';
        $this->mailer->Body    = "Hello,<br><br>You requested a password reset. Click the link below to reset your password:<br><br>"
                             . "<a href=\"$reset_link\">$reset_link</a><br><br>"
                             . "If you did not request this, you can safely ignore this email.";
        $this->mailer->send();
    }

    public function sendTestEmail($recipient_email) {
        $this->mailer->addAddress($recipient_email);
        $this->mailer->isHTML(true);
        $this->mailer->Subject = 'ARG Admin - SMTP Configuration Test';
        $this->mailer->Body    = "Hello,<br><br>This is a test email sent from your ARG admin panel.<br><br>If you received this, your SMTP settings are configured correctly!<br><br>Time: " . date('Y-m-d H:i:s');
        $this->mailer->send();
    }
}