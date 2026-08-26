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

        $sent = @mail($to, $subjectLine, $body, $headers);

        if (!$sent) {
            // mail() returning false almost always means there's no MTA (sendmail) configured
            // on this host at all — e.g. the Docker/Railway deploy target, which doesn't ship
            // one. The submission itself still gets persisted to contact_messages regardless
            // (see about.php), so nothing is lost, but nobody gets notified unless someone is
            // watching the logs — surface it loudly rather than only in a DB column an admin
            // would have to think to check. Deliberately no email/name here: that's already in
            // contact_messages under the app's normal access controls, and a plain server log
            // is the wrong place to also carry a submitter's PII.
            error_log(sprintf('Mailer::sendContactMessage failed to send (no MTA configured?) — subject "%s"', $subjectLabel));
        }

        return $sent;
    }

    /** Strips CR/LF so user input can never inject extra mail headers. */
    private static function sanitizeHeaderValue(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }
}
