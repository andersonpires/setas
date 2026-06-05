<?php
/**
 * MailService - Envio básico de e-mails HTML
 */

class MailService
{
    public static function sendHtml(string $to, string $subject, string $html, ?string $from = null, ?string $fromName = null): bool
    {
        $fromAddress = $from ?: (defined('MAIL_FROM') ? MAIL_FROM : 'no-reply@example.com');
        $fromLabel = $fromName ?: (defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'SETAS-WEB');

        $fromHeader = trim($fromLabel) !== '' ? sprintf('%s <%s>', $fromLabel, $fromAddress) : $fromAddress;
        $headers = "From: {$fromHeader}\r\n";
        $headers .= "Reply-To: {$fromHeader}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";

        return mail($to, $subject, $html, $headers);
    }
}

