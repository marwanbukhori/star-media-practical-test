<?php
declare(strict_types=1);

namespace Smg;

/**
 * The generic page shown when a request can't be completed (e.g. the database is unreachable).
 * Callers error_log() the real exception first; the visitor never sees file paths, stack traces
 * or database error internals.
 */
final class ErrorPage
{
    public static function render(int $status = 503): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/html; charset=utf-8');
        }

        echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<title>Star Media Group</title></head><body style="font-family:sans-serif;'
            . 'max-width:32rem;margin:4rem auto;padding:0 1.5rem;color:#1a1a1a;">'
            . '<h1 style="font-size:1.25rem;">We hit a snag</h1>'
            . '<p>Something went wrong loading this page. Please try again in a moment.</p>'
            . '<p><a href="/">Return to the homepage</a></p>'
            . '</body></html>';
    }
}
