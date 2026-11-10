<?php

declare(strict_types=1);

namespace App\Models;

use RuntimeException;
use Sahil\Auth\Password;
use Sahil\Contracts\Auth\AuthenticatableContract;
use Sahil\Orm\Model;

/**
 * Application user.
 *
 * The `users` table stores a one-way password hash; `createWithPassword()`
 * and `setPassword()` are the only supported write paths for that column.
 * `email_verified_at` is exposed as a `DateTimeImmutable` by the ORM's
 * `datetime` cast.
 */
final class User extends Model implements AuthenticatableContract
{
    protected ?string $table = 'users';

    protected array $fillable = ['name', 'email'];

    protected array $hidden = ['password', 'remember_token'];

    protected array $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Hydrated rows bypass mass assignment; new records only accept the
     * application-facing name and email fields.
     *
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [], bool $exists = false)
    {
        if ($exists) {
            foreach ($attributes as $key => $value) {
                $this->setRawAttribute((string) $key, $value, false);
            }

            return;
        }

        parent::__construct($attributes);
    }

    /**
     * Create an account with a one-way password hash.
     *
     * @param array<string, mixed> $options
     */
    public static function createWithPassword(
        string $name,
        string $email,
        string $password,
        array $options = [],
    ): static {
        $user = new static();
        $user->fill(['name' => $name, 'email' => $email]);
        $user->setRawAttribute('password', Password::hash($password, $options));
        $user->setRawAttribute('remember_token', null);
        $user->save();

        return $user;
    }

    public static function findByEmail(string $email): ?static
    {
        $user = static::query()->where('email', '=', $email)->first();

        return $user instanceof static ? $user : null;
    }

    public function getAuthIdentifier(): int|string
    {
        $identifier = $this->rawAttribute($this->getPrimaryKey());

        if (!is_int($identifier) && !is_string($identifier)) {
            throw new RuntimeException('The user record has no valid authentication identifier.');
        }

        return $identifier;
    }

    public function getAuthPassword(): string
    {
        $password = $this->rawAttribute('password');

        if (!is_string($password)) {
            throw new RuntimeException('The user record has no valid password hash.');
        }

        return $password;
    }

    public function getAuthIdentifierName(): string
    {
        return $this->getPrimaryKey();
    }

    /**
     * Replace the stored password with a fresh one-way hash.
     *
     * @param array<string, mixed> $options
     */
    public function setPassword(string $password, array $options = []): void
    {
        $this->setRawAttribute('password', Password::hash($password, $options));
        $this->save();
    }
}
