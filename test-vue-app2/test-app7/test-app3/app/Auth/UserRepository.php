<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\User;
use Sahil\Contracts\Auth\AuthenticatableContract;
use Sahil\Database\Connection;

/**
 * Creates and looks up application accounts.
 *
 * Passwords are hashed here, before the row is written; the plaintext value
 * only ever lives in the request and in this method's argument.
 */
final class UserRepository
{
    /**
     * @param array<string, mixed> $passwordOptions
     */
    public function __construct(
        Connection $connection,
        private readonly array $passwordOptions = [],
    ) {
        User::useConnection($connection);
    }

    public function exists(string $email): bool
    {
        return User::findByEmail($email) !== null;
    }

    /**
     * Insert an account and return it in the shape the auth layer expects.
     */
    public function create(string $name, string $email, string $password): AuthenticatableContract
    {
        return User::createWithPassword($name, $email, $password, $this->passwordOptions);
    }

    /**
     * Replace the account's password with a fresh hash of the plaintext value.
     */
    public function updatePassword(AuthenticatableContract $user, string $password): void
    {
        $model = User::find($user->getAuthIdentifier());

        if (!$model instanceof User) {
            throw new \RuntimeException('The account to update no longer exists.');
        }

        $model->setPassword($password, $this->passwordOptions);
    }
}
