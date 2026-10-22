<?php

namespace App\Http\Requests\Api;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTicketsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'overdue' => ['nullable', 'in:true,false,1,0'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => 'Status harus salah satu dari: '.implode(', ', TicketStatus::values()).'.',
            'overdue.in' => 'Parameter overdue harus true atau false.',
            'search.max' => 'Parameter search maksimal :max karakter.',
            'per_page.integer' => 'per_page harus berupa angka.',
            'per_page.min' => 'per_page minimal :min.',
            'per_page.max' => 'per_page maksimal :max.',
            'page.integer' => 'page harus berupa angka.',
            'page.min' => 'page minimal :min.',
        ];
    }

    public function statusFilter(): ?TicketStatus
    {
        $v = $this->validated('status');

        return $v ? TicketStatus::from($v) : null;
    }

    public function overdueFilter(): ?bool
    {
        $v = $this->validated('overdue');

        return $v === null ? null : filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }
}
