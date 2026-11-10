<?php

declare(strict_types=1);

use Sahil\Database\Schema\Schema;
use Sahil\Database\Schema\Table;
use Sahil\Migrations\Migration;

/**
 * Password reset tokens.
 *
 * The email address is the primary key: one outstanding reset link per
 * account. Only a SHA-256 digest of the token is stored, so a database leak
 * does not expose usable reset links.
 */
return new class () extends Migration {
    public function up(Schema $schema): void
    {
        $schema->create('password_reset_tokens', static function (Table $table): void {
            $table->string('email', 255)->primary();
            $table->string('token', 255);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('password_reset_tokens');
    }
};
