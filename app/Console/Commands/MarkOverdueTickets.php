<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * @deprecated Gunakan command `tickets:check-overdue`. Command ini dipertahankan sebagai alias kompatibilitas.
 */
class MarkOverdueTickets extends Command
{
    protected $signature = 'tickets:mark-overdue';

    protected $description = '[Deprecated] Gunakan tickets:check-overdue untuk memperbarui kolom is_overdue.';

    public function handle(): int
    {
        $this->warn('Perhatian: Command tickets:mark-overdue sudah deprecated. Mengalihkan ke tickets:check-overdue...');

        return $this->call('tickets:check-overdue');
    }
}
