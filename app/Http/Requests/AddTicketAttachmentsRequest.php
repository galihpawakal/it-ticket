<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class AddTicketAttachmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dilindungi middleware api.key
    }

    public function rules(): array
    {
        return [
            'attachments' => ['required', 'array', 'min:1', 'max:5'],
            'attachments.*' => [
                'required',
                'file',
                'max:5120',
                'mimes:pdf,jpg,jpeg,png,webp,gif,bmp',
                'mimetypes:application/pdf,image/jpeg,image/png,image/webp,image/gif,image/bmp,image/x-ms-bmp',
            ],
        ];
    }

    public function attributes(): array
    {
        $attributes = [
            'attachments' => 'Lampiran',
        ];

        $files = $this->file('attachments') ?? $this->input('attachments');
        if (is_array($files)) {
            foreach (array_keys($files) as $idx) {
                $num = is_numeric($idx) ? ((int) $idx + 1) : $idx;
                $attributes["attachments.{$idx}"] = "Lampiran {$num}";
            }
        }

        return $attributes;
    }

    public function messages(): array
    {
        return [
            'attachments.required' => 'Lampiran wajib disertakan.',
            'attachments.array' => 'Lampiran harus berupa kumpulan file.',
            'attachments.min' => 'Sertakan minimal 1 file lampiran.',
            'attachments.max' => 'Maksimal 5 lampiran per pengiriman.',
            'attachments.*.required' => ':attribute wajib disertakan.',
            'attachments.*.file' => ':attribute harus berupa file yang valid.',
            'attachments.*.max' => ':attribute melebihi 5 MB.',
            'attachments.*.mimes' => ':attribute harus berformat PDF atau foto (JPG, PNG, GIF, WEBP, BMP).',
            'attachments.*.mimetypes' => ':attribute harus berformat PDF atau foto (JPG, PNG, GIF, WEBP, BMP).',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ticket = $this->route('ticket');
            if ($ticket instanceof Ticket) {
                $existingCount = $ticket->attachments()->count();
                $newFiles = $this->file('attachments');
                $newCount = is_array($newFiles) ? count($newFiles) : 0;

                if ($existingCount + $newCount > 5) {
                    $sisa = max(0, 5 - $existingCount);
                    $validator->errors()->add(
                        'attachments',
                        "Total lampiran tidak boleh melebihi 5 file per tiket. Tiket ini sudah memiliki {$existingCount} lampiran (sisa kuota: {$sisa})."
                    );
                }
            }
        });
    }

    /**
     * @return array<UploadedFile>
     */
    public function attachmentFiles(): array
    {
        $files = $this->file('attachments');
        if (!is_array($files)) {
            return [];
        }

        return array_values(array_filter($files, fn ($f) => $f instanceof UploadedFile && $f->isValid()));
    }
}
