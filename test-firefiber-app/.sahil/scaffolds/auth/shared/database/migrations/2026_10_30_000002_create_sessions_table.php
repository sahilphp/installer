<?php

declare(strict_types=1);

use Sahil\Database\Schema\Schema;
use Sahil\Database\Schema\Table;
use Sahil\Migrations\Migration;

/**
 * Database-backed sessions.
 *
 * The default driver stores sessions on disk; this table is ready for
 * deployments that switch SESSION_DRIVER to a database driver.
 */
return new class () extends Migration {
    public function up(Schema $schema): void
    {
        $schema->create('sessions', static function (Table $table): void {
            $table->string('id', 255)->primary();
            $table->bigInteger('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity');
            $table->index('user_id');
            $table->index('last_activity');
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('sessions');
    }
};
