@extends('layouts.app')
@section('title', 'Tiket #'.$ticket->id)

@section('content')
    @php $late = $ticket->is_overdue; @endphp

    <p><a href="{{ route('tickets.index') }}">&larr; Kembali ke daftar</a></p>
    <h1>#{{ $ticket->id }} {{ $ticket->title }}</h1>

    @if ($errors->any())
        <div class="errors" role="alert">
            @foreach ($errors->all() as $message) <div>{{ $message }}</div> @endforeach
        </div>
    @endif

    <div class="panel">
        <dl class="detail">
            <dt>Status</dt>
            <dd><span class="chip st-{{ $ticket->status->value }}">{{ $ticket->status->label() }}</span>
                @if ($late) <span class="chip late-chip">Terlambat</span> @endif</dd>

            <dt>Prioritas</dt>
            <dd><span class="pr pr-{{ $ticket->priority->value }}">{{ $ticket->priority->label() }}</span></dd>

            <dt>Pelapor</dt>
            <dd>{{ $ticket->reporter_name }}</dd>

            <dt>Deskripsi</dt>
            <dd>{{ $ticket->description }}</dd>

            <dt>Lampiran ({{ $ticket->attachments->count() }})</dt>
            <dd>
                @if ($ticket->attachments->isEmpty())
                    <span class="sub">Tidak ada lampiran pada tiket ini.</span>
                @else
                    <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 4px;">
                        @foreach ($ticket->attachments as $att)
                            <div style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; background: var(--paper); border: 1px solid var(--line); border-radius: 6px;">
                                @if ($att->isImage())
                                    <a href="{{ route('tickets.attachments.show', [$ticket, $att]) }}" target="_blank" rel="noopener noreferrer" title="Buka foto ukuran penuh">
                                        <img src="{{ route('tickets.attachments.show', [$ticket, $att]) }}" alt="{{ $att->original_name }}"
                                             style="width: 56px; height: 56px; object-fit: cover; border-radius: 4px; border: 1px solid var(--line); display: block;" loading="lazy">
                                    </a>
                                @else
                                    <div style="width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; background: #fff; border: 1px solid var(--line); border-radius: 4px; font-weight: 700; font-size: 14px; color: var(--late);" title="Dokumen PDF">
                                        PDF
                                    </div>
                                @endif
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 600; word-break: break-all;">
                                        <a href="{{ route('tickets.attachments.show', [$ticket, $att]) }}" target="_blank" rel="noopener noreferrer">
                                            {{ $att->original_name }}
                                        </a>
                                    </div>
                                    <div class="sub">
                                        {{ $att->humanReadableSize() }} &bull; {{ $att->isPdf() ? 'Dokumen PDF' : 'Foto ('.strtoupper(pathinfo($att->original_name, PATHINFO_EXTENSION) ?: 'Gambar').')' }}
                                    </div>
                                </div>
                                <div>
                                    <a class="btn" href="{{ route('tickets.attachments.show', [$ticket, $att]) }}" target="_blank" rel="noopener noreferrer" style="font-size: 13px; padding: 5px 12px;">
                                        Buka / Unduh
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </dd>

            <dt>Batas waktu</dt>
            <dd>{{ $ticket->due_at->translatedFormat('l, d F Y, H:i') }} WIB
                <div class="sub {{ $late ? 'note-late' : '' }}">{{ $ticket->deadlineNote() }}</div></dd>

            @if ($ticket->done_at)
                <dt>Selesai pada</dt>
                <dd>{{ $ticket->done_at->translatedFormat('d F Y, H:i') }} WIB</dd>
            @endif

            @if ($ticket->overdue_marked_at)
                <dt>Ditandai terlambat</dt>
                <dd>{{ $ticket->overdue_marked_at->translatedFormat('d F Y, H:i') }} WIB (oleh proses terjadwal)</dd>
            @endif

            <dt>Dibuat</dt>
            <dd>{{ $ticket->created_at->translatedFormat('d F Y, H:i') }} WIB</dd>

            <dt>Diperbarui</dt>
            <dd>{{ $ticket->updated_at->translatedFormat('d F Y, H:i') }} WIB</dd>
        </dl>

        <div class="actions">
            <strong>Ubah status:</strong>
            @if ($ticket->status === \App\Enums\TicketStatus::Done)
                <span class="sub" style="font-weight: 600; color: var(--done);">Tiket ini telah selesai (Done) dan tidak dapat diubah kembali.</span>
            @else
                @foreach ($statuses as $s)
                    @php $allowed = $ticket->canTransitionTo($s); @endphp
                    <form method="POST" action="{{ route('tickets.status', $ticket) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $s->value }}">
                        <button class="btn {{ $ticket->status === $s ? '' : 'primary' }}" type="submit"
                                @disabled(!$allowed || $ticket->status === $s)
                                @if(!$allowed) title="Status tidak dapat dikembalikan lagi" @endif>
                            {{ $s->label() }}
                        </button>
                    </form>
                @endforeach
            @endif
        </div>
    </div>
@endsection
