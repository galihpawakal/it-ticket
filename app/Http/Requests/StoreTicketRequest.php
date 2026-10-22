<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/** Dipakai bersama oleh form web dan POST /api/tickets. */
class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses API dijaga middleware api.key
    }

    /**
     * Saring input file kosong jika form dikirimkan tanpa memilih file.
     */
    protected function prepareForValidation(): void
    {
        if ($this->hasFile('attachments')) {
            $files = $this->file('attachments');
            if (is_array($files)) {
                // Saring elemen null (mis. UPLOAD_ERR_NO_FILE dari input file kosong di browser)
                $filtered = array_values(array_filter($files, fn ($f) => $f instanceof UploadedFile && $f->isValid()));
                if (empty($filtered)) {
                    $this->files->remove('attachments');
                } else {
                    $this->files->set('attachments', $filtered);
                }
            }
        } elseif ($this->input('attachments') === null || $this->input('attachments') === [null] || $this->input('attachments') === '') {
            $this->request->remove('attachments');
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'reporter_name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'due_at' => ['required', 'date', 'after:now'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:5120', // Maksimal 5 MB (5120 KB)
                'mimes:pdf,jpg,jpeg,png,webp,gif,bmp',
                'mimetypes:application/pdf,image/jpeg,image/png,image/webp,image/gif,image/bmp,image/x-ms-bmp',
            ],
        ];
    }

    public function attributes(): array
    {
        $attributes = [
            'title' => 'Judul',
            'reporter_name' => 'Nama pelapor',
            'description' => 'Deskripsi',
            'priority' => 'Prioritas',
            'due_at' => 'Batas waktu',
            'attachments' => 'Lampiran',
        ];

        // Format nama tiap file sesuai urutan agar pesan error spesifik: "Lampiran 1", "Lampiran 2", dst.
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
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max.string' => ':attribute maksimal :max karakter.',
            'date' => ':attribute bukan tanggal/waktu yang valid.',
            'due_at.after' => 'Batas waktu harus lebih dari waktu sekarang (WIB).',
            'priority.enum' => 'Prioritas harus salah satu dari: '.implode(', ', TicketPriority::values()).'.',
            'attachments.array' => 'Lampiran harus berupa kumpulan file.',
            'attachments.max' => 'Maksimal 5 lampiran.',
            'attachments.*.file' => ':attribute harus berupa file yang valid.',
            'attachments.*.max' => ':attribute melebihi 5 MB.',
            'attachments.*.mimes' => ':attribute harus berformat PDF atau foto (JPG, PNG, GIF, WEBP, BMP).',
            'attachments.*.mimetypes' => ':attribute harus berformat PDF atau foto (JPG, PNG, GIF, WEBP, BMP).',
        ];
    }

    /**
     * Data siap simpan ke model Ticket.
     */
    public function ticketData(): array
    {
        $data = $this->only(['title', 'reporter_name', 'description', 'priority', 'due_at']);
        $tz = config('app.timezone');
        $data['due_at'] = Carbon::parse($data['due_at'], $tz)->setTimezone($tz);

        return $data;
    }

    /**
     * Ambil daftar file yang valid diunggah.
     *
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
