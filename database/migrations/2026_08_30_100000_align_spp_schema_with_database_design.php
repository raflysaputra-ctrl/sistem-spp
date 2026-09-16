<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bring the initial scaffold tables in line with the approved SPP schema.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('id', 'id_user');
            $table->string('nama', 100);
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->rememberToken();
        });

        Schema::table('jurusan', function (Blueprint $table) {
            $table->renameColumn('id', 'id_jurusan');
            $table->string('kode_jurusan', 10)->unique();
            $table->string('nama_jurusan', 50);
        });

        Schema::table('tahun_ajaran', function (Blueprint $table) {
            $table->renameColumn('id', 'id_tahun_ajaran');
            $table->string('tahun_ajaran', 9)->unique();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('aktif')->default(false);
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->renameColumn('id', 'id_kelas');
            $table->unsignedBigInteger('id_jurusan');
            $table->unsignedTinyInteger('tingkat');
            $table->unsignedTinyInteger('rombel');
            $table->string('nama_kelas', 30);
            $table->unique(['id_jurusan', 'tingkat', 'rombel']);
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->renameColumn('id', 'id_siswa');
            $table->string('nipd', 30)->unique();
            $table->string('nama_siswa', 100);
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->year('angkatan');
            $table->enum('status_siswa', ['aktif', 'lulus', 'pindah'])->default('aktif');
            $table->index('nama_siswa');
            $table->index('status_siswa');
            $table->index('angkatan');
        });

        Schema::table('siswa_kelas', function (Blueprint $table) {
            $table->renameColumn('id', 'id_siswa_kelas');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_kelas');
            $table->unsignedBigInteger('id_tahun_ajaran');
            $table->unique(['id_siswa', 'id_tahun_ajaran']);
        });

        Schema::table('tarif_spp', function (Blueprint $table) {
            $table->renameColumn('id', 'id_tarif');
            $table->unsignedBigInteger('id_tahun_ajaran');
            $table->unsignedTinyInteger('tingkat');
            $table->decimal('nominal', 12, 0)->unsigned();
            $table->unique(['id_tahun_ajaran', 'tingkat']);
        });

        Schema::table('tagihan_spp', function (Blueprint $table) {
            $table->renameColumn('id', 'id_tagihan');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_siswa_kelas');
            $table->unsignedBigInteger('id_tarif');
            $table->unsignedTinyInteger('bulan');
            $table->year('tahun');
            $table->decimal('nominal', 12, 0)->unsigned();
            $table->enum('status', ['belum_bayar', 'lunas'])->default('belum_bayar');
            $table->dateTime('tanggal_lunas')->nullable();
            $table->unique(['id_siswa', 'bulan', 'tahun']);
            $table->index(['tahun', 'bulan']);
            $table->index('status');
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->renameColumn('id', 'id_pembayaran');
            $table->string('no_kwitansi', 50)->unique();
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_user');
            $table->dateTime('tanggal_bayar');
            $table->decimal('total_bayar', 12, 0)->unsigned();
            $table->string('keterangan', 255)->nullable();
            $table->index('tanggal_bayar');
        });

        Schema::table('detail_pembayaran', function (Blueprint $table) {
            $table->renameColumn('id', 'id_detail_pembayaran');
            $table->unsignedBigInteger('id_pembayaran');
            $table->unsignedBigInteger('id_tagihan')->unique();
            $table->decimal('nominal_bayar', 12, 0)->unsigned();
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->foreign('id_jurusan')->references('id_jurusan')->on('jurusan');
        });

        Schema::table('siswa_kelas', function (Blueprint $table) {
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa');
            $table->foreign('id_kelas')->references('id_kelas')->on('kelas');
            $table->foreign('id_tahun_ajaran')->references('id_tahun_ajaran')->on('tahun_ajaran');
        });

        Schema::table('tarif_spp', function (Blueprint $table) {
            $table->foreign('id_tahun_ajaran')->references('id_tahun_ajaran')->on('tahun_ajaran');
        });

        Schema::table('tagihan_spp', function (Blueprint $table) {
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa');
            $table->foreign('id_siswa_kelas')->references('id_siswa_kelas')->on('siswa_kelas');
            $table->foreign('id_tarif')->references('id_tarif')->on('tarif_spp');
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa');
            $table->foreign('id_user')->references('id_user')->on('users');
        });

        Schema::table('detail_pembayaran', function (Blueprint $table) {
            $table->foreign('id_pembayaran')->references('id_pembayaran')->on('pembayaran');
            $table->foreign('id_tagihan')->references('id_tagihan')->on('tagihan_spp');
        });
    }

    /**
     * Reverse the schema alignment while leaving the scaffold tables in place.
     */
    public function down(): void
    {
        Schema::table('detail_pembayaran', function (Blueprint $table) {
            $table->dropForeign(['id_pembayaran', 'id_tagihan']);
            $table->dropUnique(['id_tagihan']);
            $table->dropColumn(['id_pembayaran', 'id_tagihan', 'nominal_bayar']);
            $table->renameColumn('id_detail_pembayaran', 'id');
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropForeign(['id_siswa', 'id_user']);
            $table->dropUnique(['no_kwitansi']);
            $table->dropIndex(['tanggal_bayar']);
            $table->dropColumn(['no_kwitansi', 'id_siswa', 'id_user', 'tanggal_bayar', 'total_bayar', 'keterangan']);
            $table->renameColumn('id_pembayaran', 'id');
        });

        Schema::table('tagihan_spp', function (Blueprint $table) {
            $table->dropForeign(['id_siswa', 'id_siswa_kelas', 'id_tarif']);
            $table->dropUnique(['id_siswa', 'bulan', 'tahun']);
            $table->dropIndex(['tahun', 'bulan']);
            $table->dropIndex(['status']);
            $table->dropColumn(['id_siswa', 'id_siswa_kelas', 'id_tarif', 'bulan', 'tahun', 'nominal', 'status', 'tanggal_lunas']);
            $table->renameColumn('id_tagihan', 'id');
        });

        Schema::table('tarif_spp', function (Blueprint $table) {
            $table->dropForeign(['id_tahun_ajaran']);
            $table->dropUnique(['id_tahun_ajaran', 'tingkat']);
            $table->dropColumn(['id_tahun_ajaran', 'tingkat', 'nominal']);
            $table->renameColumn('id_tarif', 'id');
        });

        Schema::table('siswa_kelas', function (Blueprint $table) {
            $table->dropForeign(['id_siswa', 'id_kelas', 'id_tahun_ajaran']);
            $table->dropUnique(['id_siswa', 'id_tahun_ajaran']);
            $table->dropColumn(['id_siswa', 'id_kelas', 'id_tahun_ajaran']);
            $table->renameColumn('id_siswa_kelas', 'id');
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropUnique(['nipd']);
            $table->dropIndex(['nama_siswa', 'status_siswa', 'angkatan']);
            $table->dropColumn(['nipd', 'nama_siswa', 'jenis_kelamin', 'angkatan', 'status_siswa']);
            $table->renameColumn('id_siswa', 'id');
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->dropForeign(['id_jurusan']);
            $table->dropUnique(['id_jurusan', 'tingkat', 'rombel']);
            $table->dropColumn(['id_jurusan', 'tingkat', 'rombel', 'nama_kelas']);
            $table->renameColumn('id_kelas', 'id');
        });

        Schema::table('tahun_ajaran', function (Blueprint $table) {
            $table->dropUnique(['tahun_ajaran']);
            $table->dropColumn(['tahun_ajaran', 'tanggal_mulai', 'tanggal_selesai', 'aktif']);
            $table->renameColumn('id_tahun_ajaran', 'id');
        });

        Schema::table('jurusan', function (Blueprint $table) {
            $table->dropUnique(['kode_jurusan']);
            $table->dropColumn(['kode_jurusan', 'nama_jurusan']);
            $table->renameColumn('id_jurusan', 'id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['nama', 'username', 'password', 'remember_token']);
            $table->renameColumn('id_user', 'id');
        });
    }
};
