<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // TIMESTAMP columns in MySQL 5.7 implicitly receive ON UPDATE CURRENT_TIMESTAMP
        // on the first TIMESTAMP in a table. Any UPDATE (even to unrelated columns) would
        // rewrite played_at to NOW(), corrupting timestamps and breaking the unique index.
        // DATETIME has no implicit auto-update behavior.
        DB::statement('ALTER TABLE plays MODIFY played_at DATETIME NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE plays MODIFY played_at TIMESTAMP NOT NULL');
    }
};
