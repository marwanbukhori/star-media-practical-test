<?php
declare(strict_types=1);

namespace Smg;

final class Config
{
    /** @var array<string, mixed>|null */
    private static ?array $config = null;

    /**
     * Loads config.php once and caches it. Tests define SMG_CONFIG_PATH before bootstrapping
     * to point this at a test config instead — everything else is unchanged.
     *
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        if (self::$config === null) {
            $path = defined('SMG_CONFIG_PATH') ? SMG_CONFIG_PATH : dirname(__DIR__) . '/config.php';
            self::$config = require $path;
        }

        return self::$config;
    }
}
