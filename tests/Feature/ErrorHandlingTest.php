<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PDOException;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_database_constraint_error_is_returned_as_a_clear_form_error(): void
    {
        $user = User::factory()->create();
        Jurusan::creating(fn () => throw new QueryException(
            'mysql',
            'insert into jurusan',
            [],
            new PDOException('Simulasi constraint database.'),
        ));

        try {
            $this->actingAs($user)
                ->from(route('master.jurusan.create'))
                ->post(route('master.jurusan.store'), [
                    'kode_jurusan' => 'ERR',
                    'nama_jurusan' => 'Error Handling',
                ])
                ->assertRedirect(route('master.jurusan.create'))
                ->assertSessionHasErrors([
                    'form' => 'Data tidak dapat diproses. Silakan periksa kembali input dan coba lagi.',
                ]);
        } finally {
            Jurusan::flushEventListeners();
        }
    }
}
