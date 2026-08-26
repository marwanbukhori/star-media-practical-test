<?php
declare(strict_types=1);

namespace Smg;

final class AuditLog
{
    public const ACTIONS = ['login', 'logout', 'export', 'view_record', 'change_password'];

    public static function record(string $action, ?int $adminId, ?string $username, ?string $detail = null): void
    {
        if (!in_array($action, self::ACTIONS, true)) {
            return;
        }

        $stmt = Db::connection()->prepare(
            'INSERT INTO admin_audit_log (admin_id, username, action, detail, ip_address)
             VALUES (:admin_id, :username, :action, :detail, INET6_ATON(:ip))'
        );
        $stmt->execute([
            ':admin_id' => $adminId,
            ':username' => $username !== null ? substr($username, 0, 64) : null,
            ':action' => $action,
            ':detail' => $detail !== null ? substr($detail, 0, 255) : null,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public static function recent(int $page, int $perPage): array
    {
        $pdo = Db::connection();

        $total = (int) $pdo->query('SELECT COUNT(*) AS c FROM admin_audit_log')->fetch()['c'];
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            'SELECT admin_id, username, action, detail, ip_address, created_at
             FROM admin_audit_log ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'totalPages' => $totalPages];
    }
}
