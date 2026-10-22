<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class TicketAttachmentController extends Controller
{
    /**
     * Unduh atau lihat lampiran dari disk private.
     * Foto dan PDF ditampilkan secara inline agar dapat dibuka di browser/tab baru.
     */
    public function show(Ticket $ticket, TicketAttachment $attachment): Response
    {
        if ((int) $attachment->ticket_id !== (int) $ticket->id) {
            abort(404, 'Lampiran tidak ditemukan.');
        }

        $disk = Storage::disk('local');
        if (!$disk->exists($attachment->stored_path)) {
            abort(404, 'File lampiran fisik tidak ditemukan.');
        }

        return $disk->response(
            $attachment->stored_path,
            $attachment->original_name,
            [
                'X-Content-Type-Options' => 'nosniff',
                'Content-Type' => $attachment->mime_type,
            ],
            'inline'
        );
    }
}
