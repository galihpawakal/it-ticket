<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Data contoh, termasuk tiket yang sudah terlambat. */
    public function run(): void
    {
        $rows = [
            ['Internet lantai 2 sangat lambat', 'Rina (Finance)', 'Koneksi Wi-Fi sering putus sejak pagi.', TicketPriority::High, TicketStatus::Open, now()->subHours(5)],
            ['Printer ruang HRD macet', 'Budi (HRD)', 'Kertas tersangkut dan muncul error E-04.', TicketPriority::Medium, TicketStatus::InProgress, now()->subDay()],
            ['Aplikasi absensi error 500', 'Sari (Operasional)', 'Setelah login muncul halaman Server Error.', TicketPriority::High, TicketStatus::Open, now()->addHours(6)],
            ['Minta instal Microsoft Office', 'Dimas (Marketing)', 'Laptop baru belum ada Office.', TicketPriority::Low, TicketStatus::Open, now()->addDays(3)],
            ['Email tidak bisa kirim lampiran', 'Wulan (Legal)', 'Lampiran di atas 5 MB selalu gagal.', TicketPriority::Medium, TicketStatus::InProgress, now()->addDay()],
            ['Monitor ruang rapat berkedip', 'Agus (GA)', 'Layar berkedip saat disambung HDMI.', TicketPriority::Low, TicketStatus::Done, now()->subDays(2)],
        ];

        foreach ($rows as [$title, $reporter, $desc, $priority, $status, $due]) {
            Ticket::forceCreate([
                'title' => $title,
                'reporter_name' => $reporter,
                'description' => $desc,
                'priority' => $priority,
                'status' => $status,
                'due_at' => $due,
                'done_at' => $status === TicketStatus::Done ? now()->subDays(3) : null,
            ]);
        }
    }
}
