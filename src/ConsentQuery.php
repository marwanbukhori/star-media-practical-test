<?php
declare(strict_types=1);

namespace Smg;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * Shared WHERE-clause building for the admin consent table and CSV export, so the two
 * can never drift out of sync on what a given filter combination actually means.
 */
final class ConsentQuery
{
    public const ALLOWED_STATUSES = ['accepted', 'declined', 'expired'];
    public const ALLOWED_SORTS = ['accepted_at', 'expires_at', 'consent_version'];

    public static function normalizeStatus(string $value): string
    {
        return in_array($value, self::ALLOWED_STATUSES, true) ? $value : '';
    }

    public static function normalizeSort(string $value): string
    {
        return in_array($value, self::ALLOWED_SORTS, true) ? $value : 'accepted_at';
    }

    public static function normalizeDir(string $value): string
    {
        return strtoupper($value) === 'ASC' ? 'ASC' : 'DESC';
    }

    /**
     * @return array{0: string, 1: array<string, mixed>} [$whereSql, $boundParams]
     */
    public static function buildWhere(
        string $q,
        string $status,
        string $dateFrom,
        string $dateTo,
        DateTimeZone $mstTz,
        DateTimeZone $utcTz
    ): array {
        $escapedQ = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
        $likePattern = '%' . $escapedQ . '%';

        $where = ["guid LIKE :q ESCAPE '\\\\'"];
        $params = [':q' => $likePattern];

        $now = new DateTimeImmutable('now', $utcTz);

        if ($status === 'declined') {
            $where[] = "action = 'declined'";
        } elseif ($status === 'accepted') {
            $where[] = "action = 'accepted' AND expires_at >= :now";
            $params[':now'] = $now->format('Y-m-d H:i:s');
        } elseif ($status === 'expired') {
            $where[] = "action = 'accepted' AND expires_at < :now";
            $params[':now'] = $now->format('Y-m-d H:i:s');
        }

        $fromUtc = self::mstDayStartUtc($dateFrom, $mstTz, $utcTz);
        $toUtc = self::mstDayEndUtc($dateTo, $mstTz, $utcTz);

        if ($fromUtc !== null) {
            $where[] = 'accepted_at >= :from_utc';
            $params[':from_utc'] = $fromUtc;
        }
        if ($toUtc !== null) {
            $where[] = 'accepted_at < :to_utc';
            $params[':to_utc'] = $toUtc;
        }

        return [implode(' AND ', $where), $params];
    }

    private static function mstDayStartUtc(string $ymd, DateTimeZone $mstTz, DateTimeZone $utcTz): ?string
    {
        if ($ymd === '') {
            return null;
        }
        try {
            return (new DateTimeImmutable($ymd, $mstTz))->setTimezone($utcTz)->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    }

    private static function mstDayEndUtc(string $ymd, DateTimeZone $mstTz, DateTimeZone $utcTz): ?string
    {
        if ($ymd === '') {
            return null;
        }
        try {
            return (new DateTimeImmutable($ymd, $mstTz))->modify('+1 day')->setTimezone($utcTz)->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    }
}
