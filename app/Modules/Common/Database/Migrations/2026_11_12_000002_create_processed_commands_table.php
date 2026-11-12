<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ids of the commands that were carried out, with a fingerprint of their content. */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('processed_commands', static function (Blueprint $table): void {
            // Compared byte for byte, as in memory: not by letter case, accents or trailing spaces.
            $table->string('command_id')->collation('utf8mb4_0900_bin')->primary();
            $table->char('fingerprint', 64);
            // Not read by the application: kept so that old ids can be removed once a retention period is decided.
            $table->timestamp('processed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::drop('processed_commands');
    }
};
