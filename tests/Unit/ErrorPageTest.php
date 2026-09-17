<?php
declare(strict_types=1);

namespace Smg\Tests;

use PHPUnit\Framework\TestCase;
use Smg\ErrorPage;

final class ErrorPageTest extends TestCase
{
    public function testRenderShowsAWayBackWithoutLeakingInternals(): void
    {
        ob_start();
        ErrorPage::render(503);
        $output = ob_get_clean();

        $this->assertStringContainsString('We hit a snag', $output);
        $this->assertStringContainsString('href="/"', $output);
        $this->assertStringNotContainsString('SQLSTATE', $output);
    }
}
