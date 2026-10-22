<?php

namespace Tests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Buat tiket langsung ke DB (melewati validasi) untuk menyiapkan skenario. */
    protected function makeTicket(array $overrides = []): Ticket
    {
        $status = $overrides['status'] ?? TicketStatus::Open;
        $statusEnum = $status instanceof TicketStatus ? $status : TicketStatus::tryFrom((string) $status);
        $dueAt = isset($overrides['due_at']) ? Carbon::parse($overrides['due_at']) : now()->addDay();

        $isOverdue = array_key_exists('is_overdue', $overrides)
            ? $overrides['is_overdue']
            : ($statusEnum !== TicketStatus::Done && $dueAt->lt(now()));

        return Ticket::forceCreate(array_merge([
            'title' => 'Printer rusak',
            'reporter_name' => 'Budi',
            'description' => 'Tidak bisa mencetak.',
            'priority' => TicketPriority::Medium,
            'status' => TicketStatus::Open,
            'due_at' => now()->addDay(),
            'is_overdue' => $isOverdue,
        ], $overrides));
    }
}
