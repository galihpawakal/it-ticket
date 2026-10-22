<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListTicketsRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    /** GET /api/tickets — pagination + filter status/overdue/search. */
    public function index(ListTicketsRequest $request)
    {
        $tickets = Ticket::query()
            ->with('attachments')
            ->filter($request->validated('search'), $request->statusFilter(), $request->overdueFilter())
            ->defaultOrder()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return TicketResource::collection($tickets);
    }

    /** GET /api/tickets/{ticket} */
    public function show(Ticket $ticket): TicketResource
    {
        $ticket->load('attachments');

        return new TicketResource($ticket);
    }

    /** POST /api/tickets — selalu dibuat dengan status open, mendukung multipart/form-data attachments. */
    public function store(StoreTicketRequest $request, TicketAttachmentService $attachmentService): JsonResponse
    {
        $ticket = DB::transaction(function () use ($request, $attachmentService) {
            $ticket = Ticket::createOpen($request->ticketData());
            $attachmentService->storeAttachments($ticket, $request->attachmentFiles());

            return $ticket;
        });

        return (new TicketResource($ticket->fresh(['attachments'])))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('api.tickets.show', $ticket));
    }

    /** PATCH /api/tickets/{ticket}/status */
    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket): TicketResource
    {
        $ticket->changeStatus($request->newStatus());
        $ticket->load('attachments');

        return new TicketResource($ticket);
    }
}
