<?php

use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tickets');

Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->whereNumber('ticket')->name('tickets.show');
Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->whereNumber('ticket')->name('tickets.status');

// Route khusus melihat/mengunduh lampiran dari disk private
Route::get('/tickets/{ticket}/attachments/{attachment}', [TicketAttachmentController::class, 'show'])
    ->whereNumber('ticket')
    ->whereNumber('attachment')
    ->name('tickets.attachments.show');
