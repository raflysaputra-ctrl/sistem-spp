<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL DDL is not transactional, so this also safely resumes a failed migration attempt.
        if (! Schema::hasColumn('pembayaran', 'status')) {
            Schema::table('pembayaran', function (Blueprint $table) {
                $table->enum('status', ['aktif', 'dibatalkan'])->default('aktif')->after('keterangan');
                $table->string('alasan_pembatalan', 255)->nullable()->after('status');
                $table->unsignedBigInteger('dibatalkan_oleh')->nullable()->after('alasan_pembatalan');
                $table->dateTime('dibatalkan_pada')->nullable()->after('dibatalkan_oleh');
                $table->index('status');
                $table->foreign('dibatalkan_oleh')->references('id_user')->on('users');
            });
        }

        Schema::table('detail_pembayaran', function (Blueprint $table) {
            // The existing unique index is also used by the foreign key, so replace it in this order.
            $table->index('id_tagihan');
            $table->dropUnique(['id_tagihan']);
        });
    }

    public function down(): void
    {
        Schema::table('detail_pembayaran', function (Blueprint $table) {
            $table->unique('id_tagihan');
            $table->dropIndex(['id_tagihan']);
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropForeign(['dibatalkan_oleh']);
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'alasan_pembatalan', 'dibatalkan_oleh', 'dibatalkan_pada']);
        });
    }
};
