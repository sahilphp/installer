<?php

declare(strict_types=1);

namespace App\Auth;

use Sahil\Contracts\Auth\PasswordResetTokenRepositoryContract;
use Sahil\Database\Connection;

/**
 * Password reset tokens stored in `password_reset_tokens`.
 *
 * The table is keyed by email (one outstanding link per account). Tokens are
 * kept as SHA-256 digests and stamped with a textual timestamp; expiry is
 * decided when a record is read, using the configured window. Issuing a new
 * token deletes the account's outstanding one, so a reset link works at most
 * once and an older link stops working immediately.
 */
final class DatabasePasswordResetTokenRepository implements PasswordResetTokenRepositoryContract
{
    public function __construct(
        private readonly Connection $connection,
        private readonly int $expirationMinutes = 60,
    ) {
    }

    public function create(int|string $userId, string $token, int $expirationMinutes): void
    {
        $email = $this->emailFor($userId);

        if ($email === null) {
            return;
        }

        $this->deleteUserTokens($userId);

        $this->connection->table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => self::digest($token),
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function find(int|string $userId, string $token): ?array
    {
        $email = $this->emailFor($userId);

        if ($email === null) {
            return null;
        }

        $row = $this->connection->table('password_reset_tokens')
            ->where('email', '=', $email)
            ->where('token', '=', self::digest($token))
            ->first();

        if ($row === null) {
            return null;
        }

        return ['created_at' => (string) ($row['created_at'] ?? '')];
    }

    public function isExpired(array $record): bool
    {
        $createdAt = strtotime((string) ($record['created_at'] ?? ''));

        if ($createdAt === false) {
            return true;
        }

        return time() >= $createdAt + ($this->expirationMinutes * 60);
    }

    public function delete(int|string $userId, string $token): void
    {
        $email = $this->emailFor($userId);

        if ($email === null) {
            return;
        }

        $this->connection->table('password_reset_tokens')
            ->where('email', '=', $email)
            ->where('token', '=', self::digest($token))
            ->delete();
    }

    public function deleteUserTokens(int|string $userId): void
    {
        $email = $this->emailFor($userId);

        if ($email === null) {
            return;
        }

        $this->connection->table('password_reset_tokens')
            ->where('email', '=', $email)
            ->delete();
    }

    public function deleteExpired(): void
    {
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($this->expirationMinutes * 60));

        $this->connection->table('password_reset_tokens')
            ->where('created_at', '<', $cutoff)
            ->delete();
    }

    private function emailFor(int|string $userId): ?string
    {
        $row = $this->connection->table('users')->where('id', '=', $userId)->first();

        if ($row === null || !isset($row['email']) || !is_string($row['email'])) {
            return null;
        }

        return $row['email'];
    }

    private static function digest(string $token): string
    {
        return hash('sha256', $token);
    }
}
