<?php

declare(strict_types=1);

use Sahil\Database\Schema\Schema;
use Sahil\Database\Schema\Table;
use Sahil\Migrations\Migration;

return new class () extends Migration {
    public function up(Schema $schema): void
    {
        $schema->create('failed_jobs', static function (Table $table): void {
            $table->string('id', 64)->primary();
            $table->string('queue');
            $table->text('payload');
            $table->text('exception');
            $table->timestamp('failed_at');
            $table->index('queue');
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('failed_jobs');
    }
};
