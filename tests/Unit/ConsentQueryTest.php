<?php
declare(strict_types=1);

namespace Smg\Tests;

use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Smg\ConsentQuery;

final class ConsentQueryTest extends TestCase
{
    private DateTimeZone $mstTz;
    private DateTimeZone $utcTz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mstTz = new DateTimeZone('Asia/Kuala_Lumpur');
        $this->utcTz = new DateTimeZone('UTC');
    }

    // ---------------------------------------------------------------- normalizeStatus

    public function testNormalizeStatusAcceptsEachAllowedValue(): void
    {
        foreach (ConsentQuery::ALLOWED_STATUSES as $status) {
            $this->assertSame($status, ConsentQuery::normalizeStatus($status));
        }
    }

    public function testNormalizeStatusRejectsAnythingNotOnTheWhitelist(): void
    {
        $this->assertSame('', ConsentQuery::normalizeStatus('DROP TABLE consent_log'));
        $this->assertSame('', ConsentQuery::normalizeStatus('Accepted'));
        $this->assertSame('', ConsentQuery::normalizeStatus(''));
    }

    // ---------------------------------------------------------------- normalizeSort

    public function testNormalizeSortAcceptsEachAllowedColumn(): void
    {
        foreach (ConsentQuery::ALLOWED_SORTS as $sort) {
            $this->assertSame($sort, ConsentQuery::normalizeSort($sort));
        }
    }

    public function testNormalizeSortFallsBackToAcceptedAtForAnythingUnknown(): void
    {
        $this->assertSame('accepted_at', ConsentQuery::normalizeSort('guid; DROP TABLE consent_log; --'));
        $this->assertSame('accepted_at', ConsentQuery::normalizeSort('ip_address'));
        $this->assertSame('accepted_at', ConsentQuery::normalizeSort(''));
    }

    // ---------------------------------------------------------------- normalizeDir

    public function testNormalizeDirAcceptsAscCaseInsensitively(): void
    {
        $this->assertSame('ASC', ConsentQuery::normalizeDir('asc'));
        $this->assertSame('ASC', ConsentQuery::normalizeDir('ASC'));
        $this->assertSame('ASC', ConsentQuery::normalizeDir('AsC'));
    }

    public function testNormalizeDirFallsBackToDescForAnythingElse(): void
    {
        $this->assertSame('DESC', ConsentQuery::normalizeDir('desc'));
        $this->assertSame('DESC', ConsentQuery::normalizeDir('sideways'));
        $this->assertSame('DESC', ConsentQuery::normalizeDir(''));
    }

    // ---------------------------------------------------------------- buildWhere

    public function testBuildWhereWithNoFiltersOnlyConstrainsByGuidLike(): void
    {
        [$sql, $params] = ConsentQuery::buildWhere('', '', '', '', $this->mstTz, $this->utcTz);

        $this->assertSame("guid LIKE :q ESCAPE '\\\\'", $sql);
        $this->assertSame('%%', $params[':q']);
        $this->assertArrayNotHasKey(':now', $params);
        $this->assertArrayNotHasKey(':from_utc', $params);
        $this->assertArrayNotHasKey(':to_utc', $params);
    }

    public function testBuildWhereEscapesLikeWildcardsInTheSearchTerm(): void
    {
        [, $params] = ConsentQuery::buildWhere('50%_off', '', '', '', $this->mstTz, $this->utcTz);

        $this->assertSame('%50\\%\\_off%', $params[':q']);
    }

    public function testBuildWhereWithDeclinedStatusAddsAnActionClauseOnly(): void
    {
        [$sql, $params] = ConsentQuery::buildWhere('', 'declined', '', '', $this->mstTz, $this->utcTz);

        $this->assertStringContainsString("action = 'declined'", $sql);
        $this->assertArrayNotHasKey(':now', $params);
    }

    public function testBuildWhereWithAcceptedStatusComparesExpiresAtToNow(): void
    {
        [$sql, $params] = ConsentQuery::buildWhere('', 'accepted', '', '', $this->mstTz, $this->utcTz);

        $this->assertStringContainsString("action = 'accepted' AND expires_at >= :now", $sql);
        $this->assertArrayHasKey(':now', $params);
    }

    public function testBuildWhereWithExpiredStatusComparesExpiresAtToNow(): void
    {
        [$sql, $params] = ConsentQuery::buildWhere('', 'expired', '', '', $this->mstTz, $this->utcTz);

        $this->assertStringContainsString("action = 'accepted' AND expires_at < :now", $sql);
        $this->assertArrayHasKey(':now', $params);
    }

    public function testBuildWhereWithADateRangeAddsBothBounds(): void
    {
        [$sql, $params] = ConsentQuery::buildWhere('', '', '2026-08-01', '2026-08-15', $this->mstTz, $this->utcTz);

        $this->assertStringContainsString('accepted_at >= :from_utc', $sql);
        $this->assertStringContainsString('accepted_at < :to_utc', $sql);
        // "from" is midnight MST on the 1st; "to" is midnight MST on the 16th (exclusive
        // upper bound), both converted to UTC (MST is UTC+8, so 00:00 MST = 16:00 UTC the
        // previous day).
        $this->assertSame('2026-07-31 16:00:00', $params[':from_utc']);
        $this->assertSame('2026-08-15 16:00:00', $params[':to_utc']);
    }

    public function testBuildWhereIgnoresAnUnparsableDate(): void
    {
        [$sql, $params] = ConsentQuery::buildWhere('', '', 'not-a-date', '', $this->mstTz, $this->utcTz);

        $this->assertStringNotContainsString('from_utc', $sql);
        $this->assertArrayNotHasKey(':from_utc', $params);
    }
}
