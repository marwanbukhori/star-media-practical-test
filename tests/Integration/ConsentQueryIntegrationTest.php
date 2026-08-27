<?php
declare(strict_types=1);

namespace Smg\Tests\Integration;

use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;
use Smg\ConsentQuery;
use Smg\Db;

/**
 * Runs ConsentQuery::buildWhere() through the same SELECT the admin dashboard and CSV
 * export use, against a small fixed set of consent_log rows, so a change to the WHERE
 * building can't silently start returning the wrong rows in either place.
 */
final class ConsentQueryIntegrationTest extends TestCase
{
    private const GUID_ACCEPTED_EARLY = '11111111-1111-4111-8111-100000000001';
    private const GUID_EXPIRED = '22222222-2222-4222-8222-200000000002';
    private const GUID_DECLINED = '33333333-3333-4333-8333-300000000003';
    private const GUID_ACCEPTED_LATE = '44444444-4444-4444-8444-400000000004';

    private PDO $pdo;
    private DateTimeZone $mstTz;
    private DateTimeZone $utcTz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Db::connection();
        $this->pdo->exec('TRUNCATE TABLE consent_log');
        $this->mstTz = new DateTimeZone('Asia/Kuala_Lumpur');
        $this->utcTz = new DateTimeZone('UTC');

        $this->seed(self::GUID_ACCEPTED_EARLY, 'accepted', '2026-08-01 04:00:00', '2030-01-01 00:00:00');
        $this->seed(self::GUID_EXPIRED, 'accepted', '2025-01-01 04:00:00', '2025-06-01 00:00:00');
        $this->seed(self::GUID_DECLINED, 'declined', '2026-08-10 04:00:00', '2030-01-01 00:00:00');
        $this->seed(self::GUID_ACCEPTED_LATE, 'accepted', '2026-08-15 04:00:00', '2030-01-01 00:00:00');
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('TRUNCATE TABLE consent_log');
        parent::tearDown();
    }

    public function testNoFiltersReturnsAllRowsNewestAcceptedFirst(): void
    {
        $this->assertSame(
            [self::GUID_ACCEPTED_LATE, self::GUID_DECLINED, self::GUID_ACCEPTED_EARLY, self::GUID_EXPIRED],
            $this->runQuery('', '', '', '')
        );
    }

    public function testStatusAcceptedExcludesDeclinedAndExpiredRows(): void
    {
        $this->assertSame(
            [self::GUID_ACCEPTED_LATE, self::GUID_ACCEPTED_EARLY],
            $this->runQuery('', 'accepted', '', '')
        );
    }

    public function testStatusDeclinedReturnsOnlyTheDeclinedRow(): void
    {
        $this->assertSame([self::GUID_DECLINED], $this->runQuery('', 'declined', '', ''));
    }

    public function testStatusExpiredReturnsOnlyTheExpiredAcceptedRow(): void
    {
        $this->assertSame([self::GUID_EXPIRED], $this->runQuery('', 'expired', '', ''));
    }

    public function testStatusExpiredAlsoIncludesADeclinedRowPastItsOneDayWindow(): void
    {
        // Older accepted_at than GUID_EXPIRED so the DESC-ordered assertion below is
        // deterministic rather than relying on tiebreak behavior for an identical timestamp.
        $guidExpiredDecline = '55555555-5555-4555-8555-500000000005';
        $this->seed($guidExpiredDecline, 'declined', '2024-01-01 04:00:00', '2024-01-02 04:00:00');

        $this->assertSame(
            [self::GUID_EXPIRED, $guidExpiredDecline],
            $this->runQuery('', 'expired', '', '')
        );
        $this->assertSame([self::GUID_DECLINED], $this->runQuery('', 'declined', '', ''));
    }

    public function testSearchTermMatchesOnlyTheGuidContainingIt(): void
    {
        $this->assertSame([self::GUID_EXPIRED], $this->runQuery('2222', '', '', ''));
    }

    public function testDateRangeExcludesRowsOutsideItRegardlessOfStatus(): void
    {
        // 2026-08-01 through 2026-08-15 MST covers the early-accepted and declined rows
        // (and the late-accepted row, whose accepted_at falls on the last included day)
        // but not the 2025 expired row.
        $this->assertSame(
            [self::GUID_ACCEPTED_LATE, self::GUID_DECLINED, self::GUID_ACCEPTED_EARLY],
            $this->runQuery('', '', '2026-08-01', '2026-08-15')
        );
    }

    public function testDateRangeCombinedWithStatusAppliesBothFilters(): void
    {
        $this->assertSame(
            [self::GUID_ACCEPTED_LATE, self::GUID_ACCEPTED_EARLY],
            $this->runQuery('', 'accepted', '2026-08-01', '2026-08-15')
        );
    }

    /** @return list<string> guids in result order */
    private function runQuery(string $q, string $status, string $dateFrom, string $dateTo): array
    {
        [$whereSql, $params] = ConsentQuery::buildWhere($q, $status, $dateFrom, $dateTo, $this->mstTz, $this->utcTz);

        $stmt = $this->pdo->prepare("SELECT guid FROM consent_log WHERE {$whereSql} ORDER BY accepted_at DESC");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function seed(string $guid, string $action, string $acceptedAt, string $expiresAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO consent_log (guid, action, consent_version, accepted_at, expires_at)
             VALUES (:guid, :action, 1, :accepted_at, :expires_at)'
        );
        $stmt->execute([
            ':guid' => $guid,
            ':action' => $action,
            ':accepted_at' => $acceptedAt,
            ':expires_at' => $expiresAt,
        ]);
    }
}
