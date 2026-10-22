<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    private array $headers = ['X-API-Key' => 'kunci-test'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ticket_api.key' => 'kunci-test']);
    }

    private function validPayload(array $o = []): array
    {
        return array_merge([
            'title' => 'Internet mati',
            'reporter_name' => 'Rina',
            'description' => 'Tidak bisa akses internet sama sekali.',
            'priority' => 'high',
            'due_at' => now()->addDay()->toIso8601String(),
        ], $o);
    }

    public function test_tanpa_api_key_ditolak_401(): void
    {
        $this->getJson('/api/tickets')->assertStatus(401)->assertJson(['message' => 'API key tidak valid.']);
    }

    public function test_api_key_salah_ditolak_401(): void
    {
        $this->getJson('/api/tickets', ['X-API-Key' => 'salah'])->assertStatus(401);
    }

    public function test_daftar_tiket_berpagination(): void
    {
        foreach (range(1, 3) as $i) {
            $this->makeTicket(['title' => "Tiket $i"]);
        }

        $this->getJson('/api/tickets?per_page=2', $this->headers)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2);
    }

    public function test_filter_status(): void
    {
        $this->makeTicket(['status' => TicketStatus::Open]);
        $this->makeTicket(['status' => TicketStatus::Done, 'done_at' => now()]);

        $this->getJson('/api/tickets?status=done', $this->headers)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'done');
    }

    public function test_filter_terlambat_hanya_yang_belum_done_dan_lewat_batas(): void
    {
        $late = $this->makeTicket(['due_at' => now()->subHour()]);
        $this->makeTicket(['due_at' => now()->addHour()]);
        $this->makeTicket(['due_at' => now()->subDay(), 'status' => TicketStatus::Done, 'done_at' => now()->subDays(2)]);

        $this->getJson('/api/tickets?overdue=true', $this->headers)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $late->id)
            ->assertJsonPath('data.0.is_overdue', true);

        $this->getJson('/api/tickets?overdue=false', $this->headers)->assertJsonCount(2, 'data');
    }

    public function test_filter_tidak_valid_422(): void
    {
        $this->getJson('/api/tickets?status=selesai', $this->headers)
            ->assertStatus(422)->assertJsonValidationErrors(['status']);
        $this->getJson('/api/tickets?overdue=mungkin', $this->headers)
            ->assertStatus(422)->assertJsonValidationErrors(['overdue']);
    }

    public function test_detail_tiket(): void
    {
        $t = $this->makeTicket();

        $this->getJson("/api/tickets/{$t->id}", $this->headers)
            ->assertOk()
            ->assertJsonPath('data.id', $t->id)
            ->assertJsonPath('data.title', 'Printer rusak');
    }

    public function test_detail_tidak_ada_404(): void
    {
        $this->getJson('/api/tickets/999', $this->headers)
            ->assertStatus(404)->assertJson(['message' => 'Tiket tidak ditemukan.']);
        $this->getJson('/api/tickets/1abc', $this->headers)
            ->assertStatus(404)->assertJson(['message' => 'Tiket tidak ditemukan.']);
    }

    public function test_buat_tiket_201_dan_status_otomatis_open(): void
    {
        $this->postJson('/api/tickets', $this->validPayload(['status' => 'done']), $this->headers)
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')   // status dari klien diabaikan
            ->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseCount('tickets', 1);
    }

    public function test_buat_tiket_input_kosong_422_dengan_pesan_jelas(): void
    {
        $this->postJson('/api/tickets', [], $this->headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'reporter_name', 'description', 'priority', 'due_at'])
            ->assertJsonPath('errors.title.0', 'Judul wajib diisi.');
    }

    public function test_batas_waktu_di_masa_lalu_ditolak(): void
    {
        $this->postJson('/api/tickets', $this->validPayload(['due_at' => now()->subHour()->toIso8601String()]), $this->headers)
            ->assertStatus(422)->assertJsonValidationErrors(['due_at']);
    }

    public function test_prioritas_tidak_valid_ditolak(): void
    {
        $this->postJson('/api/tickets', $this->validPayload(['priority' => 'urgent']), $this->headers)
            ->assertStatus(422)->assertJsonValidationErrors(['priority']);
    }

    public function test_ubah_status_mengisi_done_at_saat_selesai(): void
    {
        $t = $this->makeTicket();

        $this->patchJson("/api/tickets/{$t->id}/status", ['status' => 'in_progress'], $this->headers)
            ->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->assertNull($t->fresh()->done_at);

        $this->patchJson("/api/tickets/{$t->id}/status", ['status' => 'done'], $this->headers)
            ->assertOk()->assertJsonPath('data.status', 'done');
        $this->assertNotNull($t->fresh()->done_at);
    }

    public function test_status_in_progress_dan_done_tidak_bisa_dibalikkan_lagi_422(): void
    {
        // 1. In progress tidak bisa kembali ke open
        $inProgress = $this->makeTicket(['status' => TicketStatus::InProgress]);
        $this->patchJson("/api/tickets/{$inProgress->id}/status", ['status' => 'open'], $this->headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // 2. Done tidak bisa kembali ke open maupun in_progress
        $done = $this->makeTicket(['status' => TicketStatus::Done, 'done_at' => now()]);
        $this->patchJson("/api/tickets/{$done->id}/status", ['status' => 'open'], $this->headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->patchJson("/api/tickets/{$done->id}/status", ['status' => 'in_progress'], $this->headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_ubah_status_tidak_valid_422_dan_tiket_tidak_ada_404(): void
    {
        $t = $this->makeTicket();

        $this->patchJson("/api/tickets/{$t->id}/status", ['status' => 'closed'], $this->headers)
            ->assertStatus(422)->assertJsonValidationErrors(['status']);
        $this->patchJson('/api/tickets/999/status', ['status' => 'done'], $this->headers)
            ->assertStatus(404);
    }
}
