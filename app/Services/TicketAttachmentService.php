<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TicketAttachmentService
{
    public const DISK = 'local';
    public const DIRECTORY = 'attachments';
    public const MAX_PER_TICKET = 5;

    /**
     * Simpan file lampiran ke disk private dan simpan record ke database.
     * Menggunakan transaksi: jika ada file gagal disimpan atau DB error,
     * file fisik yang sudah terlanjur ditulis akan dibersihkan.
     *
     * @param Ticket $ticket
     * @param array<UploadedFile> $files
     * @return array<TicketAttachment>
     * @throws Throwable
     */
    public function storeAttachments(Ticket $ticket, array $files): array
    {
        if (empty($files)) {
            return [];
        }

        $storedPaths = [];
        $attachments = [];

        try {
            DB::beginTransaction();

            $disk = Storage::disk(self::DISK);

            foreach ($files as $file) {
                if (!$file instanceof UploadedFile || !$file->isValid()) {
                    continue;
                }

                // Nama acak: UUID + ekstensi aman
                $extension = strtolower($file->getClientOriginalExtension());
                $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'];
                if ($extension === '' || !in_array($extension, $allowed, true)) {
                    $extension = $file->guessExtension() ?: 'bin';
                }
                $storedFileName = Str::uuid()->toString() . '.' . $extension;

                // Simpan ke storage/app/private/attachments/...
                $storedPath = $disk->putFileAs(self::DIRECTORY, $file, $storedFileName);
                if (!$storedPath) {
                    throw new \RuntimeException('Gagal menulis file lampiran ke penyimpanan.');
                }
                $storedPaths[] = $storedPath;

                $originalName = self::sanitizeOriginalName($file->getClientOriginalName());

                $attachment = new TicketAttachment([
                    'original_name' => $originalName,
                    'stored_path' => $storedPath,
                    'mime_type' => $file->getClientMimeType() ?: $file->getMimeType() ?: 'application/octet-stream',
                    'size' => $file->getSize(),
                ]);

                $ticket->attachments()->save($attachment);
                $attachments[] = $attachment;
            }

            DB::commit();

            return $attachments;
        } catch (Throwable $e) {
            DB::rollBack();

            // Bersihkan file yang sudah terlanjur disimpan di disk
            $disk = Storage::disk(self::DISK);
            foreach ($storedPaths as $path) {
                if ($disk->exists($path)) {
                    $disk->delete($path);
                }
            }

            throw $e;
        }
    }

    /**
     * Sanitasi nama asli file: buang path traversal, karakter berbahaya/kontrol, batasi panjang.
     */
    public static function sanitizeOriginalName(string $name): string
    {
        $name = basename(trim($name));
        $name = str_replace(["\0", "\r", "\n", '"', "'", ';', '\\'], '', $name);
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name);

        if (mb_strlen($name) > 200) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $base = pathinfo($name, PATHINFO_FILENAME);
            $name = mb_substr($base, 0, 190) . ($ext ? '.' . $ext : '');
        }

        return $name !== '' ? $name : 'attachment';
    }
}
