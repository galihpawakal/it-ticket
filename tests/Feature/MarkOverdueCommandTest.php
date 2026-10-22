<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkOverdueCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_tiket_terlambat_yang_belum_done_yang_ditandai(): void
    {
        $late = $this->makeTicket(['due_at' => now()->subHour()]);
        $onTime = $this->makeTicket(['due_at' => now()->addHour()]);
        $doneLate = $this->makeTicket(['due_at' => now()->subDay(), 'status' => TicketStatus::Done, 'done_at' => now()]);

        $this->artisan('tickets:mark-overdue')->assertSuccessful();

        $this->assertNotNull($late->fresh()->overdue_marked_at);
        $this->assertNull($onTime->fresh()->overdue_marked_at);
        $this->assertNull($doneLate->fresh()->overdue_marked_at);
    }

    public function test_tidak_menandai_ulang_tiket_yang_sudah_ditandai(): void
    {
        $t = $this->makeTicket(['due_at' => now()->subHour(), 'overdue_marked_at' => now()->subHour()]);
        $before = $t->fresh()->overdue_marked_at;

        $this->artisan('tickets:mark-overdue')->assertSuccessful();

        $this->assertEquals($before, $t->fresh()->overdue_marked_at);
    }
}
