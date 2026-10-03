<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->changeRoleDomain(['petugas', 'admin', 'tu', 'kepala_sekolah', 'siswa'], 'tu');

        DB::table('users')->where('role', 'petugas')->update(['role' => 'tu']);

        $this->changeRoleDomain(['admin', 'tu', 'kepala_sekolah', 'siswa'], 'tu');
    }

    public function down(): void
    {
        $this->changeRoleDomain(['petugas', 'admin', 'tu', 'kepala_sekolah', 'siswa'], 'petugas');

        DB::table('users')
            ->whereIn('role', ['admin', 'tu', 'kepala_sekolah'])
            ->update(['role' => 'petugas']);

        $this->changeRoleDomain(['petugas', 'siswa'], 'petugas');
    }

    /**
     * @param  list<string>  $roles
     */
    private function changeRoleDomain(array $roles, string $default): void
    {
        if (DB::getDriverName() === 'mysql') {
            $values = collect($roles)
                ->map(fn (string $role) => "'".str_replace("'", "''", $role)."'")
                ->implode(', ');

            DB::statement("ALTER TABLE users MODIFY role ENUM({$values}) NOT NULL DEFAULT '{$default}'");

            return;
        }

        Schema::table('users', function (Blueprint $table) use ($roles, $default) {
            $table->enum('role', $roles)->default($default)->change();
        });
    }
};
