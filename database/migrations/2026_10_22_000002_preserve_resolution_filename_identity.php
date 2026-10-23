<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // A and Á can identify different physical files. Search stays accent-insensitive.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE resolution_folders MODIFY relative_path VARCHAR(700) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
            DB::statement('ALTER TABLE resolutions MODIFY filename VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
        }
    }

    public function down(): void
    {
        // Keep exact identity: reverting to an accent-insensitive unique index may merge files.
    }
};
