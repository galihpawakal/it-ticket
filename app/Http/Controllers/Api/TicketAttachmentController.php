<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddTicketAttachmentsRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Services\TicketAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class TicketAttachmentController extends Controller
{
    /**
     * GET /api/tickets/{ticket}/attachments/{attachment}
     * Unduh/lihat lampiran tiket melalui API (wajib X-API-Key).
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

    /**
     * POST /api/tickets/{ticket}/attachments
     * Tambah lampiran ke tiket yang sudah ada (menghormati kuota total maksimal 5 file).
     */
    public function store(
        AddTicketAttachmentsRequest $request,
        Ticket $ticket,
        TicketAttachmentService $service
    ): JsonResponse {
        $service->storeAttachments($ticket, $request->attachmentFiles());

        return (new TicketResource($ticket->fresh(['attachments'])))
            ->response()
            ->setStatusCode(201);
    }
}
