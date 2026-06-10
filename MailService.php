<?php
class MailService {
    public function sendResetEmail($email, $token) {
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password?token=" . $token;
        
        $subject = "Password Reset Request - Smart AgroLink System";
        $message = "Hello,\n\n";
        $message .= "We received a request to reset the password for your account associated with this email address.\n\n";
        $message .= "Your password reset code is: " . $token . "\n\n";
        $message .= "Alternatively, you can click the link below:\n";
        $message .= $resetLink . "\n\n";
        $message .= "This link will expire in 1 hour.\n";
        $message .= "If you did not request a password reset, no further action is required.\n\n";
        $message .= "Best regards,\nSmart AgroLink Team";

        // Email Headers
        $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n";
        $headers .= "Reply-To: support@" . $_SERVER['HTTP_HOST'] . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Attempt to send the real email via the server's mail system
        $mailSent = mail($email, $subject, $message, $headers);
        
        // Maintain a log for auditing and local testing
        $logFile = __DIR__ . '/mail_log.txt';
        $logEntry = "[" . date('Y-m-d H:i:s') . "] To: $email | Status: " . ($mailSent ? 'Sent' : 'Failed') . "\n";
        $logEntry .= $message . "\n--------------------------\n";
        
        @file_put_contents($logFile, $logEntry, FILE_APPEND);

        return $mailSent;
    }
}