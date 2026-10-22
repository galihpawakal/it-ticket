<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Ticket */
class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'reporter_name' => $this->reporter_name,
            'description' => $this->description,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'due_at' => $this->due_at->toIso8601String(),          // +07:00 (WIB)
            'is_overdue' => (bool) $this->is_overdue,
            'overdue_marked_at' => $this->overdue_marked_at?->toIso8601String(),
            'done_at' => $this->done_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'attachments' => $this->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'url' => route('api.tickets.attachments.show', [
                    'ticket' => $this->id,
                    'attachment' => $attachment->id,
                ]),
            ])->values()->all(),
        ];
    }
}
