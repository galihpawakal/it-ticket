<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    /** status dan is_overdue TIDAK boleh diisi massal dari input pengguna. */
    protected $fillable = [
        'title',
        'reporter_name',
        'description',
        'priority',
        'due_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'is_overdue' => 'boolean',
            'due_at' => 'datetime',
            'done_at' => 'datetime',
            'overdue_marked_at' => 'datetime',
        ];
    }

    /**
     * Hapus semua lampiran (dan file fisiknya) saat tiket dihapus.
     */
    protected static function booted(): void
    {
        static::deleting(function (Ticket $ticket) {
            $ticket->attachments()->each(function (TicketAttachment $attachment) {
                $attachment->delete();
            });
        });
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /* ------------------------------------------------------------------ */
    /* Aturan bisnis                                                       */
    /* ------------------------------------------------------------------ */

    /**
     * Logika tunggal penentu keterlambatan tiket:
     * Terlambat jika dan hanya jika status BUKAN Done DAN batas waktu sudah lewat dari waktu acuan (WIB).
     */
    public function computeOverdue(?CarbonInterface $now = null): bool
    {
        $now ??= now();

        return $this->status !== TicketStatus::Done
            && $this->due_at !== null
            && $this->due_at->lt($now);
    }

    /** Tiket baru otomatis berstatus Open dan is_overdue dihitung konsisten. */
    public static function createOpen(array $data): self
    {
        $ticket = new self($data);
        $ticket->status = TicketStatus::Open;
        $ticket->is_overdue = $ticket->computeOverdue();
        $ticket->save();

        return $ticket;
    }

    /**
     * Cek apakah transisi ke status baru diizinkan.
     * Aturan bisnis:
     * - Status saat ini In progress: tidak dapat dikembalikan ke Open.
     * - Status saat ini Done: tidak dapat diubah kembali ke status lain.
     * - Mengubah ke status yang sama tetap diizinkan (idempotent).
     */
    public function canTransitionTo(TicketStatus $new): bool
    {
        if ($this->status === $new) {
            return true;
        }

        if ($this->status === TicketStatus::Done) {
            return false;
        }

        if ($this->status === TicketStatus::InProgress && $new === TicketStatus::Open) {
            return false;
        }

        return true;
    }

    /**
     * Ubah status. done_at diisi saat Done.
     * is_overdue dihitung ulang (jika Done menjadi false).
     * Perubahan disimpan bersama status dalam satu save().
     * Mengubah ke status yang sama tidak mengubah apa pun (idempotent).
     */
    public function changeStatus(TicketStatus $new): self
    {
        if ($this->status === $new) {
            return $this;
        }

        if (!$this->canTransitionTo($new)) {
            throw new \InvalidArgumentException("Status tiket tidak dapat diubah dari {$this->status->value} ke {$new->value}.");
        }

        $this->status = $new;
        $this->done_at = $new === TicketStatus::Done ? now() : null;
        $this->is_overdue = $this->computeOverdue();
        $this->save();

        return $this;
    }

    /** Nilai keterlambatan yang tersimpan di kolom database is_overdue. */
    public function isOverdue(): bool
    {
        return (bool) $this->is_overdue;
    }

    /** Teks penanda untuk UI: "Terlambat 2 hari", "Sisa 3 jam", atau status penyelesaian. */
    public function deadlineNote(): string
    {
        $span = ['syntax' => CarbonInterface::DIFF_ABSOLUTE, 'parts' => 1];

        if ($this->status === TicketStatus::Done) {
            if ($this->done_at === null) {
                return 'Selesai';
            }

            return $this->done_at->lte($this->due_at) ? 'Selesai tepat waktu' : 'Selesai terlambat';
        }

        return $this->isOverdue()
            ? 'Terlambat '.$this->due_at->diffForHumans($span)
            : 'Sisa '.$this->due_at->diffForHumans($span);
    }

    /* ------------------------------------------------------------------ */
    /* Query scope                                                         */
    /* ------------------------------------------------------------------ */

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('is_overdue', true);
    }

    public function scopeNotOverdue(Builder $query): Builder
    {
        return $query->where('is_overdue', false);
    }

    public function scopeSearchTitle(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        // Escape wildcard LIKE supaya "50%" dicari sebagai teks biasa. '!' dipakai sebagai karakter escape.
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);

        return $query->whereRaw("title LIKE ? ESCAPE '!'", ['%'.$escaped.'%']);
    }

    /** Filter gabungan yang dipakai halaman web dan REST API. */
    public function scopeFilter(Builder $query, ?string $search = null, ?TicketStatus $status = null, ?bool $overdue = null): Builder
    {
        $query->searchTitle($search);

        if ($status !== null) {
            $query->where('status', $status->value);
        }
        if ($overdue === true) {
            $query->overdue();
        } elseif ($overdue === false) {
            $query->notOverdue();
        }

        return $query;
    }

    /** Urutan: tiket belum selesai dulu, batas waktu terdekat di atas. */
    public function scopeDefaultOrder(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")
            ->orderBy('due_at')
            ->orderByDesc('id');
    }
}
