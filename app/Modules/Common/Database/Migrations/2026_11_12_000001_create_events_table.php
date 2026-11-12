<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** The event store. Stored events are facts: the database itself refuses to change or delete them (ADR 0013). */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('events', static function (Blueprint $table): void {
            $table->id('position');
            // Ids and names compare byte for byte, as in memory: not by letter case, accents or trailing spaces.
            $table->string('stream_id')->collation('utf8mb4_0900_bin');
            $table->unsignedInteger('stream_version');
            $table->string('event_type')->collation('utf8mb4_0900_bin');
            $table->json('payload');
            $table->unique(['stream_id', 'stream_version']);
        });

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER events_are_not_updated BEFORE UPDATE ON events FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stored events cannot be changed.'
            SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER events_are_not_deleted BEFORE DELETE ON events FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stored events cannot be deleted.'
            SQL);
    }

    public function down(): void
    {
        Schema::drop('events');
    }
};
