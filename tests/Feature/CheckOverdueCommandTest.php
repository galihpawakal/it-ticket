<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CheckOverdueCommandTest extends TestCase
{
    use RefreshDatabase;

    private array $apiHeaders = ['X-API-Key' => 'kunci-test'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ticket_api.key' => 'kunci-test']);
    }

    public function test_open_melewati_batas_waktu_menjadi_terlambat_setelah_command(): void
    {
        Carbon::setTestNow('2026-10-22 10:00:00');

        // Tiket open dengan due_at pukul 11:00 (awalnya belum terlambat)
        $ticket = Ticket::createOpen([
            'title' => 'Server down',
            'reporter_name' => 'Budi',
            'description' => 'Server tidak merespons.',
            'priority' => TicketPriority::High,
            'due_at' => Carbon::parse('2026-10-22 11:00:00'),
        ]);

        $this->assertFalse($ticket->is_overdue);

        // Waktu berjalan ke pukul 12:00 (batas waktu terlewati)
        Carbon::setTestNow('2026-10-22 12:00:00');

        // Sebelum command berjalan, kolom is_overdue masih false (menunggu jadwal/command)
        $this->assertFalse($ticket->fresh()->is_overdue);

        // Jalankan command
        $this->artisan('tickets:check-overdue')
            ->expectsOutputToContain('1 jadi terlambat')
            ->assertSuccessful();

        $this->assertTrue($ticket->fresh()->is_overdue);
        $this->assertNotNull($ticket->fresh()->overdue_marked_at);
    }

    public function test_in_progress_sebelum_batas_waktu_tidak_terlambat(): void
    {
        Carbon::setTestNow('2026-10-22 10:00:00');

        $ticket = $this->makeTicket([
            'status' => TicketStatus::InProgress,
            'due_at' => Carbon::parse('2026-10-22 15:00:00'),
            'is_overdue' => false,
        ]);

        $this->artisan('tickets:check-overdue')->assertSuccessful();

        $this->assertFalse($ticket->fresh()->is_overdue);
    }

    public function test_done_melewati_batas_waktu_tetap_tidak_terlambat(): void
    {
        Carbon::setTestNow('2026-10-22 12:00:00');

        // Tiket berstatus Done meskipun due_at di masa lalu
        $ticket = $this->makeTicket([
            'status' => TicketStatus::Done,
            'due_at' => Carbon::parse('2026-10-22 09:00:00'),
            'done_at' => Carbon::parse('2026-10-22 11:00:00'),
            'is_overdue' => false,
        ]);

        $this->artisan('tickets:check-overdue')->assertSuccessful();

        $this->assertFalse($ticket->fresh()->is_overdue);
    }

    public function test_status_in_progress_dan_done_tidak_bisa_dibalikkan_lagi_lewat_web(): void
    {
        Carbon::setTestNow('2026-10-22 14:00:00');

        // 1. In progress tidak bisa dibalikkan ke Open
        $inProgress = $this->makeTicket([
            'status' => TicketStatus::InProgress,
            'due_at' => Carbon::parse('2026-10-22 10:00:00'),
        ]);

        $res1 = $this->patch("/tickets/{$inProgress->id}/status", ['status' => 'open']);
        $res1->assertSessionHasErrors(['status']);
        $this->assertEquals(TicketStatus::InProgress, $inProgress->fresh()->status);

        // 2. Done tidak bisa dibalikkan ke Open maupun In progress
        $done = $this->makeTicket([
            'status' => TicketStatus::Done,
            'due_at' => Carbon::parse('2026-10-22 10:00:00'),
            'done_at' => Carbon::parse('2026-10-22 11:00:00'),
        ]);

        $res2 = $this->patch("/tickets/{$done->id}/status", ['status' => 'open']);
        $res2->assertSessionHasErrors(['status']);
        $this->assertEquals(TicketStatus::Done, $done->fresh()->status);

        $res3 = $this->patch("/tickets/{$done->id}/status", ['status' => 'in_progress']);
        $res3->assertSessionHasErrors(['status']);
        $this->assertEquals(TicketStatus::Done, $done->fresh()->status);
    }

    public function test_change_status_melempar_exception_bila_transisi_mundur(): void
    {
        $inProgress = $this->makeTicket(['status' => TicketStatus::InProgress]);
        $done = $this->makeTicket(['status' => TicketStatus::Done, 'done_at' => now()]);

        $this->expectException(\InvalidArgumentException::class);
        $inProgress->changeStatus(TicketStatus::Open);
    }

    public function test_open_terlambat_lalu_didone_lewat_web_dan_api_menjadi_false(): void
    {
        Carbon::setTestNow('2026-10-22 12:00:00');

        // 1. Lewat Web
        $ticketWeb = $this->makeTicket([
            'status' => TicketStatus::Open,
            'due_at' => Carbon::parse('2026-10-22 09:00:00'),
            'is_overdue' => true,
        ]);

        $resWeb = $this->patch("/tickets/{$ticketWeb->id}/status", [
            'status' => 'done',
        ]);
        $resWeb->assertRedirect();
        $this->assertFalse($ticketWeb->fresh()->is_overdue);
        $this->assertEquals(TicketStatus::Done, $ticketWeb->fresh()->status);

        // 2. Lewat API
        $ticketApi = $this->makeTicket([
            'status' => TicketStatus::Open,
            'due_at' => Carbon::parse('2026-10-22 09:00:00'),
            'is_overdue' => true,
        ]);

        $resApi = $this->patchJson("/api/tickets/{$ticketApi->id}/status", [
            'status' => 'done',
        ], $this->apiHeaders);

        $resApi->assertOk()
            ->assertJsonPath('data.status', 'done')
            ->assertJsonPath('data.is_overdue', false);

        $this->assertFalse($ticketApi->fresh()->is_overdue);
    }

    public function test_data_tidak_konsisten_dikoreksi_oleh_command(): void
    {
        Carbon::setTestNow('2026-10-22 12:00:00');

        // Tiket Done tapi is_overdue masih true (anomali)
        $inconsistentDone = $this->makeTicket([
            'status' => TicketStatus::Done,
            'due_at' => Carbon::parse('2026-10-22 09:00:00'),
            'is_overdue' => true,
        ]);

        // Tiket Open batas waktu masih masa depan tapi is_overdue = true (anomali)
        $inconsistentFuture = $this->makeTicket([
            'status' => TicketStatus::Open,
            'due_at' => Carbon::parse('2026-10-22 18:00:00'),
            'is_overdue' => true,
        ]);

        $this->artisan('tickets:check-overdue')
            ->expectsOutputToContain('2 kembali tidak terlambat')
            ->assertSuccessful();

        $this->assertFalse($inconsistentDone->fresh()->is_overdue);
        $this->assertFalse($inconsistentFuture->fresh()->is_overdue);
    }

    public function test_command_idempotent_dijalankan_dua_kali_berturut_turut(): void
    {
        Carbon::setTestNow('2026-10-22 12:00:00');

        $late = $this->makeTicket([
            'status' => TicketStatus::Open,
            'due_at' => Carbon::parse('2026-10-22 09:00:00'),
            'is_overdue' => false,
        ]);

        // Run pertama: mengubah 1 tiket menjadi terlambat
        $this->artisan('tickets:check-overdue')
            ->expectsOutputToContain('1 jadi terlambat')
            ->assertSuccessful();

        $this->assertTrue($late->fresh()->is_overdue);

        // Run kedua: 0 tiket diubah, kondisi akhir sama persis
        $this->artisan('tickets:check-overdue')
            ->expectsOutputToContain('0 jadi terlambat, 0 kembali tidak terlambat (total diubah 0)')
            ->assertSuccessful();

        $this->assertTrue($late->fresh()->is_overdue);
    }

    public function test_output_command_memuat_jumlah_diperiksa_dan_diubah(): void
    {
        Carbon::setTestNow('2026-10-22 12:00:00');

        $this->makeTicket(['status' => TicketStatus::Open, 'due_at' => Carbon::parse('2026-10-22 09:00:00'), 'is_overdue' => false]);
        $this->makeTicket(['status' => TicketStatus::Done, 'due_at' => Carbon::parse('2026-10-22 08:00:00'), 'is_overdue' => true]);
        $this->makeTicket(['status' => TicketStatus::Open, 'due_at' => Carbon::parse('2026-10-22 17:00:00'), 'is_overdue' => false]);

        // Total tiket diperiksa = 3; 1 jadi terlambat, 1 kembali tidak terlambat (total diubah 2)
        $this->artisan('tickets:check-overdue')
            ->expectsOutput('Diperiksa 3 tiket; 1 jadi terlambat, 1 kembali tidak terlambat (total diubah 2).')
            ->assertSuccessful();
    }

    public function test_tiket_baru_is_overdue_false_dan_filter_api_tetap_benar(): void
    {
        Carbon::setTestNow('2026-10-22 10:00:00');

        $res = $this->postJson('/api/tickets', [
            'title' => 'Printer macet',
            'reporter_name' => 'Siti',
            'description' => 'Kertas tersangkut.',
            'priority' => 'low',
            'due_at' => '2026-10-23T10:00:00+07:00',
        ], $this->apiHeaders);

        $res->assertCreated()
            ->assertJsonPath('data.is_overdue', false);

        // Buat satu tiket terlambat untuk filter
        $this->makeTicket([
            'title' => 'Tiket Lama Terlambat',
            'is_overdue' => true,
        ]);

        $filterTrue = $this->getJson('/api/tickets?overdue=true', $this->apiHeaders);
        $filterTrue->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Tiket Lama Terlambat');

        $filterFalse = $this->getJson('/api/tickets?overdue=false', $this->apiHeaders);
        $filterFalse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Printer macet');
    }
}
