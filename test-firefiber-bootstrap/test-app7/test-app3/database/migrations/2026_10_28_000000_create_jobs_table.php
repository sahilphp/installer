<?php

declare(strict_types=1);

use Sahil\Database\Schema\Schema;
use Sahil\Database\Schema\Table;
use Sahil\Migrations\Migration;

return new class () extends Migration {
    public function up(Schema $schema): void
    {
        $schema->create('jobs', static function (Table $table): void {
            $table->string('id', 64)->primary();
            $table->string('queue');
            $table->text('payload');
            $table->integer('attempts')->default(0);
            $table->timestamp('available_at');
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index('queue');
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('jobs');
    }
};
