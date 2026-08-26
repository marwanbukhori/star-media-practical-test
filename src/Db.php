<?php
declare(strict_types=1);

namespace Smg;

use PDO;
use PDOException;

final class Db
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $config = require dirname(__DIR__) . '/config.php';
        $db = $config['db'];

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );

        // PHP 8.4+ deprecates PDO::MYSQL_ATTR_INIT_COMMAND in favor of the driver-specific
        // Pdo\Mysql::ATTR_INIT_COMMAND, but the new class doesn't exist before 8.4 and this
        // project supports 8.2+. constant() resolves it at runtime so the class_exists() check
        // short-circuits before PHP ever needs to parse the newer name on older versions.
        $initCommandAttr = class_exists('Pdo\\Mysql')
            ? constant('Pdo\\Mysql::ATTR_INIT_COMMAND')
            : PDO::MYSQL_ATTR_INIT_COMMAND;

        try {
            self::$instance = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // The app treats every DATETIME/TIMESTAMP value as UTC (see Consent.php,
                // Auth.php). MySQL's session time_zone defaults to the server's system zone,
                // which would silently shift TIMESTAMP columns (login_attempts.attempted_at,
                // admin_users.created_at, etc. — anything using CURRENT_TIMESTAMP) away from
                // UTC. Pin it so reads/writes are consistent regardless of server config.
                $initCommandAttr => "SET time_zone = '+00:00'",
            ]);
        } catch (PDOException $e) {
            throw new PDOException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode());
        }

        return self::$instance;
    }
}
