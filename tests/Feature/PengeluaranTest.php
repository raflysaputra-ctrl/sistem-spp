<?php

namespace Tests\Feature;

use App\Models\KategoriPengeluaran;
use App\Models\Pengeluaran;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PengeluaranTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tu_can_record_an_expense(): void
    {
        $tu = User::factory()->tu()->create();
        $kategori = KategoriPengeluaran::create(['nama_kategori' => 'Listrik dan Internet']);

        $this->actingAs($tu)
            ->get(route('pengeluaran.index'))
            ->assertOk()
            ->assertSee('data-currency-input', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertSeeText(['Input Pengeluaran', 'Listrik dan Internet', 'Ringkasan entri']);

        $this->actingAs($tu)->post(route('pengeluaran.store'), [
            'id_kategori_pengeluaran' => $kategori->id_kategori_pengeluaran,
            'tanggal_pengeluaran' => '2026-10-08',
            'keterangan' => 'Pembayaran listrik bulan Oktober.',
            'nominal' => 500_000,
        ])->assertRedirect();

        $pengeluaran = Pengeluaran::query()->sole();
        $this->assertSame($tu->id_user, $pengeluaran->id_user);
        $this->assertSame('aktif', $pengeluaran->status);
        $this->assertSame(500_000, (int) $pengeluaran->nominal);

        $this->actingAs($tu)->get(route('pengeluaran.detail', $pengeluaran))
            ->assertOk()
            ->assertSeeText(['Listrik dan Internet', 'Rp 500.000', 'Pembayaran listrik bulan Oktober.']);
    }

    public function test_admin_can_cancel_expense_without_deleting_history(): void
    {
        $admin = User::factory()->admin()->create();
        $tu = User::factory()->tu()->create();
        $kategori = KategoriPengeluaran::create(['nama_kategori' => 'Kegiatan Sekolah']);
        $pengeluaran = Pengeluaran::create([
            'id_kategori_pengeluaran' => $kategori->id_kategori_pengeluaran,
            'id_user' => $tu->id_user,
            'tanggal_pengeluaran' => '2026-10-08',
            'keterangan' => 'Kebutuhan kegiatan sekolah.',
            'nominal' => 750_000,
        ]);

        $this->actingAs($admin)->patch(route('pengeluaran.batalkan', $pengeluaran), [
            'alasan_pembatalan' => 'Nominal dicatat dua kali.',
            'password' => 'password',
        ])->assertRedirect(route('pengeluaran.detail', $pengeluaran));

        $pengeluaran->refresh();
        $this->assertSame('dibatalkan', $pengeluaran->status);
        $this->assertSame('Nominal dicatat dua kali.', $pengeluaran->alasan_pembatalan);
        $this->assertSame($admin->id_user, $pengeluaran->dibatalkan_oleh);
        $this->assertNotNull($pengeluaran->dibatalkan_pada);
        $this->assertDatabaseHas('pengeluaran', ['id_pengeluaran' => $pengeluaran->id_pengeluaran]);
    }

    public function test_tu_cannot_cancel_expense_and_admin_cannot_record_one(): void
    {
        $admin = User::factory()->admin()->create();
        $tu = User::factory()->tu()->create();
        $kategori = KategoriPengeluaran::create(['nama_kategori' => 'Pemeliharaan Sarana']);
        $pengeluaran = Pengeluaran::create([
            'id_kategori_pengeluaran' => $kategori->id_kategori_pengeluaran,
            'id_user' => $tu->id_user,
            'tanggal_pengeluaran' => '2026-10-08',
            'keterangan' => 'Perbaikan fasilitas.',
            'nominal' => 200_000,
        ]);

        $this->actingAs($tu)->patch(route('pengeluaran.batalkan', $pengeluaran), [
            'alasan_pembatalan' => 'Tidak jadi.',
            'password' => 'password',
        ])->assertForbidden();

        $this->actingAs($admin)->get(route('pengeluaran.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('pengeluaran.store'), [
            'id_kategori_pengeluaran' => $kategori->id_kategori_pengeluaran,
            'tanggal_pengeluaran' => '2026-10-08',
            'keterangan' => 'Tidak boleh dicatat Admin.',
            'nominal' => 1,
        ])->assertForbidden();
    }

    public function test_nonactive_category_cannot_be_used_for_new_expense(): void
    {
        $tu = User::factory()->tu()->create();
        $kategori = KategoriPengeluaran::create([
            'nama_kategori' => 'Alat Tulis Kantor',
            'aktif' => false,
        ]);

        $this->actingAs($tu)->from(route('pengeluaran.index'))->post(route('pengeluaran.store'), [
            'id_kategori_pengeluaran' => $kategori->id_kategori_pengeluaran,
            'tanggal_pengeluaran' => '2026-10-08',
            'keterangan' => 'Pembelian alat tulis.',
            'nominal' => 100_000,
        ])->assertRedirect(route('pengeluaran.index'))
            ->assertSessionHasErrors('id_kategori_pengeluaran');

        $this->assertDatabaseCount('pengeluaran', 0);
    }
}
