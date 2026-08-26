<?php
declare(strict_types=1);

namespace Smg;

final class Mailer
{
    public static function subjectLabel(string $key): string
    {
        return match ($key) {
            'advertising_partnerships' => 'Advertising and partnerships',
            'editorial_enquiry' => 'Editorial enquiry',
            'data_privacy_request' => 'Data and privacy request',
            default => 'General enquiry',
        };
    }

    public static function sendContactMessage(string $name, string $email, string $subjectKey, string $message): bool
    {
        $mailConfig = Config::get()['mail'];

        $safeName = self::sanitizeHeaderValue($name);
        $safeEmail = self::sanitizeHeaderValue($email);
        $subjectLabel = self::subjectLabel($subjectKey);

        $to = $mailConfig['to_address'];
        $subjectLine = self::sanitizeHeaderValue(sprintf('[Star Media Group] %s — %s', $subjectLabel, $safeName));

        $body = "New contact form submission\n\n"
            . "Name: {$safeName}\n"
            . "Email: {$safeEmail}\n"
            . "Subject: {$subjectLabel}\n\n"
            . "Message:\n{$message}\n";

        $headers = sprintf(
            "From: %s <%s>\r\nReply-To: %s\r\nContent-Type: text/plain; charset=UTF-8\r\n",
            self::sanitizeHeaderValue($mailConfig['from_name']),
            self::sanitizeHeaderValue($mailConfig['from_address']),
            $safeEmail
        );

        return @mail($to, $subjectLine, $body, $headers);
    }

    /** Strips CR/LF so user input can never inject extra mail headers. */
    private static function sanitizeHeaderValue(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }
}
