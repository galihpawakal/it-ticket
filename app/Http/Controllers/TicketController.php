<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Models\Ticket;
use App\Services\TicketAttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = TicketStatus::tryFrom((string) $request->query('status', ''));

        $tickets = Ticket::query()
            ->withCount('attachments')
            ->filter($search, $status)
            ->defaultOrder()
            ->paginate(10)
            ->withQueryString();

        $counts = Ticket::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('tickets.index', [
            'tickets' => $tickets,
            'search' => $search,
            'status' => $status,
            'counts' => $counts,
            'allCount' => $counts->sum(),
            'overdueCount' => Ticket::overdue()->count(),
        ]);
    }

    public function create(): View
    {
        return view('tickets.create', [
            'priorities' => TicketPriority::cases(),
            'minDue' => now()->addMinute()->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(StoreTicketRequest $request, TicketAttachmentService $attachmentService): RedirectResponse
    {
        $ticket = DB::transaction(function () use ($request, $attachmentService) {
            $ticket = Ticket::createOpen($request->ticketData());
            $attachmentService->storeAttachments($ticket, $request->attachmentFiles());

            return $ticket;
        });

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Tiket #{$ticket->id} berhasil dibuat dengan status Open.");
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load('attachments');

        return view('tickets.show', [
            'ticket' => $ticket,
            'statuses' => TicketStatus::cases(),
        ]);
    }

    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket->changeStatus($request->newStatus());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Status tiket #{$ticket->id} sekarang {$ticket->status->label()}.");
    }
}
