<?php

namespace App\Console\Commands;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckOverdueTickets extends Command
{
    protected $signature = 'tickets:check-overdue';

    protected $description = 'Periksa dan perbarui status keterlambatan (is_overdue) tiket serta koreksi data tidak konsisten.';

    public function handle(): int
    {
        $now = now();

        // Jumlah tiket diperiksa: total seluruh tiket yang ada di database saat proses evaluasi berjalan
        $totalChecked = Ticket::count();

        // 1. Ubah ke TRUE: tiket belum selesai (non-done) yang batas waktunya telah lewat (due_at < now)
        //    dan saat ini kolom is_overdue masih false.
        $becameOverdue = Ticket::query()
            ->where('status', '!=', TicketStatus::Done->value)
            ->where('due_at', '<', $now)
            ->where('is_overdue', false)
            ->update([
                'is_overdue' => true,
            ]);

        // Simpan waktu deteksi keterlambatan pertama kali bila belum terisi
        Ticket::query()
            ->where('is_overdue', true)
            ->whereNull('overdue_marked_at')
            ->update(['overdue_marked_at' => $now]);

        // 2. Ubah ke FALSE: tiket yang tercatat is_overdue = true tetapi seharusnya TIDAK terlambat
        //    (misalnya berstatus Done, atau due_at >= now), sehingga data tidak konsisten terkoreksi.
        $becameNotOverdue = Ticket::query()
            ->where('is_overdue', true)
            ->where(function ($query) use ($now) {
                $query->where('status', TicketStatus::Done->value)
                    ->orWhere('due_at', '>=', $now);
            })
            ->update([
                'is_overdue' => false,
            ]);

        $totalChanged = $becameOverdue + $becameNotOverdue;

        $message = "Diperiksa {$totalChecked} tiket; {$becameOverdue} jadi terlambat, {$becameNotOverdue} kembali tidak terlambat (total diubah {$totalChanged}).";

        Log::info($message);
        $this->info($message);

        return self::SUCCESS;
    }
}
