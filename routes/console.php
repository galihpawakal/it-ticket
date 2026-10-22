<?php

use Illuminate\Support\Facades\Schedule;

// Proses otomatis terjadwal: perbarui status is_overdue setiap 5 menit.
// Di server, pasang cron per menit: * * * * * cd /path/ke/proyek && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('tickets:check-overdue')
    ->everyFiveMinutes()
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();
