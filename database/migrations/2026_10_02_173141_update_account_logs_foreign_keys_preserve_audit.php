<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['actor_id']);

            $table->foreign('user_id')->references('id_user')->on('users')->onDelete('restrict');
            $table->foreign('actor_id')->references('id_user')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('account_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['actor_id']);

            $table->foreign('user_id')->references('id_user')->on('users')->onDelete('cascade');
            $table->foreign('actor_id')->references('id_user')->on('users')->onDelete('cascade');
        });
    }
};
