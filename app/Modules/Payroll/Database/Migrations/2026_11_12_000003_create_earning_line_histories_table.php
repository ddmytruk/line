<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The histories of lines for reading: built from the events, so they can be dropped and built again (ADR 0014). */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('earning_line_histories', static function (Blueprint $table): void {
            // Compared byte for byte, as in memory.
            $table->string('line_id')->collation('utf8mb4_0900_bin')->primary();
            $table->json('history');
        });
    }

    public function down(): void
    {
        Schema::drop('earning_line_histories');
    }
};
