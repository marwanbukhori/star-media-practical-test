<?php
declare(strict_types=1);

namespace Smg\Tests;

use PHPUnit\Framework\TestCase;
use Smg\Csrf;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Session is process-wide for the life of the PHPUnit run — reset the token
        // between tests so each one starts from a clean slate.
        $_SESSION = [];
    }

    public function testTokenGeneratesA64CharacterHexString(): void
    {
        $token = Csrf::token();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function testTokenIsStableAcrossCallsWithinTheSameSession(): void
    {
        $first = Csrf::token();
        $second = Csrf::token();

        $this->assertSame($first, $second);
    }

    public function testVerifyAcceptsTheMatchingToken(): void
    {
        $token = Csrf::token();

        $this->assertTrue(Csrf::verify($token));
    }

    public function testVerifyRejectsAWrongToken(): void
    {
        Csrf::token();

        $this->assertFalse(Csrf::verify('0000000000000000000000000000000000000000000000000000000000000000'));
    }

    public function testVerifyRejectsNull(): void
    {
        Csrf::token();

        $this->assertFalse(Csrf::verify(null));
    }

    public function testVerifyRejectsAnEmptyString(): void
    {
        Csrf::token();

        $this->assertFalse(Csrf::verify(''));
    }

    public function testVerifyRejectsWhenNoTokenWasEverIssued(): void
    {
        // No Csrf::token() call this time — nothing in $_SESSION to compare against.
        $this->assertFalse(Csrf::verify('anything'));
    }

    public function testFieldRendersAHiddenInputContainingTheToken(): void
    {
        $html = Csrf::field();

        $this->assertStringStartsWith('<input type="hidden" name="csrf_token" value="', $html);
        $this->assertStringContainsString(Csrf::token(), $html);
    }
}
