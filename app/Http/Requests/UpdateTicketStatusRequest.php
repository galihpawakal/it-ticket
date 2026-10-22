<?php

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TicketStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status wajib diisi.',
            'status.enum' => 'Status harus salah satu dari: '.implode(', ', TicketStatus::values()).'.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ticket = $this->route('ticket');
            if (!$ticket instanceof Ticket) {
                $ticket = Ticket::find($ticket);
            }

            if ($ticket && $this->filled('status')) {
                $newStatus = TicketStatus::tryFrom((string) $this->input('status'));
                if ($newStatus !== null && !$ticket->canTransitionTo($newStatus)) {
                    if ($ticket->status === TicketStatus::Done) {
                        $validator->errors()->add(
                            'status',
                            'Tiket sudah selesai (Done) dan tidak dapat diubah kembali statusnya.'
                        );
                    } elseif ($ticket->status === TicketStatus::InProgress && $newStatus === TicketStatus::Open) {
                        $validator->errors()->add(
                            'status',
                            'Tiket sudah dalam proses (In progress) dan tidak dapat dikembalikan ke status Open.'
                        );
                    } else {
                        $validator->errors()->add(
                            'status',
                            "Perubahan status dari {$ticket->status->label()} ke {$newStatus->label()} tidak diizinkan."
                        );
                    }
                }
            }
        });
    }

    public function newStatus(): TicketStatus
    {
        return TicketStatus::from($this->validated('status'));
    }
}
