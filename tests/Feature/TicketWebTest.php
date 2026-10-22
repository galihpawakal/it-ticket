<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_daftar_menampilkan_tiket_dan_penanda_terlambat(): void
    {
        $this->makeTicket(['title' => 'Wi-Fi putus', 'due_at' => now()->subHours(3)]);

        $this->get('/tickets')
            ->assertOk()
            ->assertSee('Wi-Fi putus')
            ->assertSee('Terlambat');
    }

    public function test_pencarian_berdasarkan_judul_dan_filter_status(): void
    {
        $this->makeTicket(['title' => 'Printer macet']);
        $this->makeTicket(['title' => 'Wi-Fi putus', 'status' => TicketStatus::Done, 'done_at' => now()]);

        $this->get('/tickets?q=Printer')->assertSee('Printer macet')->assertDontSee('Wi-Fi putus');
        $this->get('/tickets?status=done')->assertSee('Wi-Fi putus')->assertDontSee('Printer macet');
    }

    public function test_karakter_persen_dicari_sebagai_teks_biasa(): void
    {
        $this->makeTicket(['title' => 'Disk penuh 100%']);
        $this->makeTicket(['title' => 'Disk penuh sekali']);

        $this->get('/tickets?q=100%25')->assertSee('Disk penuh 100%')->assertDontSee('Disk penuh sekali');
    }

    public function test_buat_tiket_berhasil_status_open(): void
    {
        $this->post('/tickets', [
            'title' => 'Aplikasi error',
            'reporter_name' => 'Sari',
            'description' => 'Muncul error 500 saat login.',
            'priority' => 'high',
            'due_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $this->assertSame(TicketStatus::Open, Ticket::firstOrFail()->status);
    }

    public function test_form_kosong_menampilkan_pesan_error_server(): void
    {
        $this->from('/tickets/create')->post('/tickets', [])
            ->assertRedirect('/tickets/create')
            ->assertSessionHasErrors(['title', 'reporter_name', 'description', 'priority', 'due_at']);

        $this->followingRedirects()->from('/tickets/create')->post('/tickets', [])
            ->assertSee('Judul wajib diisi.');
    }

    public function test_detail_dan_ubah_status(): void
    {
        $t = $this->makeTicket(['title' => 'Monitor berkedip']);

        $this->get("/tickets/{$t->id}")->assertOk()->assertSee('Monitor berkedip');

        $this->patch("/tickets/{$t->id}/status", ['status' => 'in_progress'])->assertRedirect();
        $this->assertSame(TicketStatus::InProgress, $t->fresh()->status);
    }

    public function test_detail_tidak_ada_404(): void
    {
        $this->get('/tickets/999')->assertNotFound();
    }
}
