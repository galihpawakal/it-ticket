@extends('layouts.app')
@section('title', 'Daftar tiket')

@section('content')
    <h1>Daftar tiket</h1>

    @php
        $tab = fn (?string $s) => route('tickets.index', array_filter(['status' => $s, 'q' => $search]));
    @endphp
    <div class="tabs" role="navigation" aria-label="Filter status">
        <a class="tab {{ $status === null ? 'active' : '' }}" href="{{ $tab(null) }}">Semua <b>{{ $allCount }}</b></a>
        @foreach (\App\Enums\TicketStatus::cases() as $s)
            <a class="tab {{ $status === $s ? 'active' : '' }}" href="{{ $tab($s->value) }}">{{ $s->label() }} <b>{{ $counts[$s->value] ?? 0 }}</b></a>
        @endforeach
        @if ($overdueCount > 0)
            <span class="tab" style="color: var(--late); border-color: var(--late);">Terlambat <b style="color: var(--late);">{{ $overdueCount }}</b></span>
        @endif
    </div>

    <form class="search" method="GET" action="{{ route('tickets.index') }}">
        <input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Cari judul tiket…" aria-label="Cari judul tiket">
        <select name="status" aria-label="Filter status" style="width:auto">
            <option value="">Semua status</option>
            @foreach (\App\Enums\TicketStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected($status === $s)>{{ $s->label() }}</option>
            @endforeach
        </select>
        <button class="btn primary" type="submit">Cari</button>
        @if ($search !== '' || $status)
            <a class="btn" href="{{ route('tickets.index') }}">Reset</a>
        @endif
    </form>

    <div class="panel">
        @if ($tickets->isEmpty())
            <div class="empty">
                @if ($search !== '' || $status)
                    Tidak ada tiket yang cocok dengan pencarian ini. <a href="{{ route('tickets.index') }}">Tampilkan semua tiket</a>.
                @else
                    Belum ada tiket. <a href="{{ route('tickets.create') }}">Buat tiket pertama</a>.
                @endif
            </div>
        @else
            <div class="scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Judul</th><th>Pelapor</th><th>Prioritas</th><th>Status</th><th>Batas waktu</th><th>Penanda</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($tickets as $t)
                        @php $late = $t->is_overdue; @endphp
                        <tr class="{{ $late ? 'late' : '' }}">
                            <td>
                                <a class="title" href="{{ route('tickets.show', $t) }}">{{ $t->title }}</a>
                                <div class="sub">
                                    #{{ $t->id }}
                                    @php $attCount = $t->attachments_count ?? $t->attachments->count(); @endphp
                                    @if ($attCount > 0)
                                        &bull; <span title="{{ $attCount }} lampiran">📎 {{ $attCount }} file</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $t->reporter_name }}</td>
                            <td><span class="pr pr-{{ $t->priority->value }}">{{ $t->priority->label() }}</span></td>
                            <td><span class="chip st-{{ $t->status->value }}">{{ $t->status->label() }}</span></td>
                            <td>{{ $t->due_at->translatedFormat('d M Y, H:i') }} WIB</td>
                            <td>
                                @if ($late) <span class="chip late-chip">Terlambat</span> @endif
                                <div class="sub {{ $late ? 'note-late' : '' }}">{{ $t->deadlineNote() }}</div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{ $tickets->links('pagination.default') }}
@endsection
