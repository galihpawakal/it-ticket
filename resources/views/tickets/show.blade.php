<x-layouts.app :title="'Tiket #' . $ticket->id">
    @php
        $late = (bool) $ticket->is_overdue;
    @endphp

    <div class="mb-3">
        <a href="{{ route('tickets.index') }}" class="btn btn-link text-decoration-none px-0 text-secondary">
            &larr; Kembali ke daftar
        </a>
    </div>

    @if ($errors->any())
        <x-alert type="danger" :dismissible="true">
            @foreach ($errors->all() as $message)
                <div>{{ $message }}</div>
            @endforeach
        </x-alert>
    @endif

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-2">#{{ $ticket->id }} {{ $ticket->title }}</h1>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <x-status-badge :status="$ticket->status" />
                <x-priority-badge :priority="$ticket->priority" />
                @if ($late)
                    <x-overdue-badge />
                @endif
            </div>
        </div>

        <!-- Ubah Status sebagai .btn-group -->
        <div class="d-flex align-items-center gap-2">
            @if ($ticket->status === \App\Enums\TicketStatus::Done)
                <span class="badge text-bg-success-subtle text-success border border-success-subtle p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-check-circle-fill me-1" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                    Tiket telah selesai (Done) dan tidak dapat diubah kembali.
                </span>
            @else
                <div class="btn-group shadow-sm" role="group" aria-label="Ubah status tiket">
                    @foreach ($statuses as $s)
                        @php
                            $isCurrent = $ticket->status === $s;
                            $allowed = $ticket->canTransitionTo($s);
                        @endphp
                        <form method="POST" action="{{ route('tickets.status', $ticket) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $s->value }}">
                            <button class="btn btn-sm {{ $isCurrent ? 'btn-primary active fw-bold' : ($allowed ? 'btn-outline-secondary' : 'btn-outline-secondary disabled') }}"
                                    type="submit"
                                    @disabled(!$allowed || $isCurrent)
                                    @if(!$allowed) title="Status tidak dapat dikembalikan lagi" @endif>
                                {{ $s->label() }}
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Informasi Detail Tiket dalam Card -->
    <x-card class="mb-4">
        <x-description-list>
            <x-description-item label="Status">
                <div class="d-flex align-items-center gap-2">
                    <x-status-badge :status="$ticket->status" />
                    @if ($late)
                        <x-overdue-badge />
                    @endif
                </div>
            </x-description-item>

            <x-description-item label="Prioritas">
                <x-priority-badge :priority="$ticket->priority" />
            </x-description-item>

            <x-description-item label="Pelapor">
                <span class="fw-semibold text-body">{{ $ticket->reporter_name }}</span>
            </x-description-item>

            <x-description-item label="Deskripsi">
                <div class="text-body" style="white-space: pre-line;">{{ $ticket->description }}</div>
            </x-description-item>

            <x-description-item label="Batas waktu">
                <div class="text-body">{{ $ticket->due_at->translatedFormat('l, d F Y, H:i') }} WIB</div>
                <div class="small {{ $late ? 'text-danger fw-semibold' : 'text-muted' }} mt-1">
                    {{ $ticket->deadlineNote() }}
                </div>
            </x-description-item>

            @if ($ticket->done_at)
                <x-description-item label="Selesai pada">
                    <span class="text-body">{{ $ticket->done_at->translatedFormat('d F Y, H:i') }} WIB</span>
                </x-description-item>
            @endif

            @if ($ticket->overdue_marked_at)
                <x-description-item label="Ditandai terlambat">
                    <span class="text-body">{{ $ticket->overdue_marked_at->translatedFormat('d F Y, H:i') }} WIB</span>
                    <span class="text-muted small ms-1">(oleh proses terjadwal)</span>
                </x-description-item>
            @endif

            <x-description-item label="Dibuat">
                <span class="text-body">{{ $ticket->created_at->translatedFormat('d F Y, H:i') }} WIB</span>
            </x-description-item>

            <x-description-item label="Diperbarui">
                <span class="text-body">{{ $ticket->updated_at->translatedFormat('d F Y, H:i') }} WIB</span>
            </x-description-item>
        </x-description-list>
    </x-card>

    <!-- Daftar Lampiran dalam Card -->
    <x-card :title="'Lampiran (' . $ticket->attachments->count() . ')'">
        @if ($ticket->attachments->isEmpty())
            <p class="text-muted small mb-0">Tidak ada lampiran pada tiket ini.</p>
        @else
            <div class="row g-3">
                @foreach ($ticket->attachments as $att)
                    <div class="col-12 col-md-6">
                        <div class="d-flex align-items-center gap-3 p-3 bg-body-tertiary border rounded shadow-sm h-100">
                            @if ($att->isImage())
                                <a href="{{ route('tickets.attachments.show', [$ticket, $att]) }}" target="_blank" rel="noopener noreferrer" title="Buka foto ukuran penuh">
                                    <img src="{{ route('tickets.attachments.show', [$ticket, $att]) }}"
                                         alt="{{ $att->original_name }}"
                                         class="attachment-thumb"
                                         loading="lazy">
                                </a>
                            @else
                                <div class="attachment-badge-pdf" title="Dokumen PDF">
                                    PDF
                                </div>
                            @endif

                            <div class="flex-grow-1 min-w-0">
                                <a href="{{ route('tickets.attachments.show', [$ticket, $att]) }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="fw-semibold text-decoration-none text-body-emphasis text-break d-block">
                                    {{ $att->original_name }}
                                </a>
                                <div class="text-muted small mt-1">
                                    {{ $att->humanReadableSize() }} &bull; {{ $att->isPdf() ? 'Dokumen PDF' : 'Foto (' . strtoupper(pathinfo($att->original_name, PATHINFO_EXTENSION) ?: 'Gambar') . ')' }}
                                </div>
                            </div>

                            <div>
                                <x-button :href="route('tickets.attachments.show', [$ticket, $att])"
                                          variant="outline" size="sm"
                                          target="_blank" rel="noopener noreferrer">
                                    Buka / Unduh
                                </x-button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</x-layouts.app>
