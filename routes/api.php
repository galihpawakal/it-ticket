<?php

use App\Http\Controllers\Api\TicketAttachmentController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

// Prefix "/api" otomatis dari bootstrap/app.php. Semua endpoint wajib header X-API-Key.
Route::middleware('api.key')->group(function () {
    Route::get('/tickets', [TicketController::class, 'index'])->name('api.tickets.index');
    Route::post('/tickets', [TicketController::class, 'store'])->name('api.tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->whereNumber('ticket')->name('api.tickets.show');
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->whereNumber('ticket')->name('api.tickets.status');

    // Lampiran tiket: unduh file privat & unggah lampiran ke tiket yang ada
    Route::get('/tickets/{ticket}/attachments/{attachment}', [TicketAttachmentController::class, 'show'])
        ->whereNumber('ticket')
        ->whereNumber('attachment')
        ->name('api.tickets.attachments.show');

    Route::post('/tickets/{ticket}/attachments', [TicketAttachmentController::class, 'store'])
        ->whereNumber('ticket')
        ->name('api.tickets.attachments.store');
});
