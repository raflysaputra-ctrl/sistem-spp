<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('siswa', 'nis') && ! Schema::hasColumn('siswa', 'nipd')) {
            // Drop unique index if exists before rename (MySQL will update index column name, but keep old index name)
            try {
                Schema::table('siswa', function ($table) {
                    $table->dropUnique(['nis']);
                });
            } catch (Throwable) {
                // ignore if index name differs
            }

            DB::statement('ALTER TABLE `siswa` CHANGE `nis` `nipd` VARCHAR(30) NOT NULL');

            Schema::table('siswa', function ($table) {
                $table->unique('nipd');
            });
        } elseif (! Schema::hasColumn('siswa', 'nipd')) {
            Schema::table('siswa', function ($table) {
                $table->string('nipd', 30)->unique()->after('id_siswa');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('siswa', 'nipd') && ! Schema::hasColumn('siswa', 'nis')) {
            try {
                Schema::table('siswa', function ($table) {
                    $table->dropUnique(['nipd']);
                });
            } catch (Throwable) {
            }

            DB::statement('ALTER TABLE `siswa` CHANGE `nipd` `nis` VARCHAR(30) NOT NULL');

            Schema::table('siswa', function ($table) {
                $table->unique('nis');
            });
        }
    }
};
