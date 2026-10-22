<x-layouts.app title="Daftar tiket">
    <x-page-header title="Daftar tiket" />

    @php
        $tab = fn (?string $s, ?bool $ov = null) => route('tickets.index', array_filter([
            'status' => $s,
            'overdue' => $ov !== null ? ($ov ? '1' : '0') : null,
            'q' => $search !== '' ? $search : null,
        ], fn ($v) => $v !== null));
    @endphp

    <!-- Filter Tab Status -->
    <x-filter-tabs>
        <x-tab :href="$tab(null, null)" :active="$status === null && $overdue === null" :count="$allCount">
            Semua
        </x-tab>
        @foreach (\App\Enums\TicketStatus::cases() as $s)
            <x-tab :href="$tab($s->value, null)" :active="$status === $s && $overdue === null" :count="$counts[$s->value] ?? 0">
                {{ $s->label() }}
            </x-tab>
        @endforeach
        @if ($overdueCount > 0)
            <x-tab :href="$tab(null, true)" :active="$overdue === true" :count="$overdueCount" variant="danger">
                Terlambat
            </x-tab>
        @endif
    </x-filter-tabs>

    <!-- Toolbar Pencarian & Filter Form -->
    <form class="row g-2 mb-4" method="GET" action="{{ route('tickets.index') }}">
        <div class="col-12 col-sm-6 col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-body text-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                    </svg>
                </span>
                <input type="search" name="q" class="form-control" value="{{ $search }}" maxlength="100"
                       placeholder="Cari judul tiket…" aria-label="Cari judul tiket">
            </div>
        </div>
        <div class="col-12 col-sm-4 col-md-3">
            <select name="status" class="form-select" aria-label="Filter status">
                <option value="">Semua status</option>
                @foreach (\App\Enums\TicketStatus::cases() as $s)
                    <option value="{{ $s->value }}" @selected($status === $s)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        @if ($overdue !== null)
            <input type="hidden" name="overdue" value="{{ $overdue ? '1' : '0' }}">
        @endif
        <div class="col-auto d-flex gap-2">
            <x-button type="submit" variant="primary">
                Cari
            </x-button>
            @if ($search !== '' || $status || $overdue !== null)
                <x-button :href="route('tickets.index')" variant="outline">
                    Reset
                </x-button>
            @endif
        </div>
    </form>

    <!-- Konten Data Tiket -->
    <x-card class="p-0 overflow-hidden">
        @if ($tickets->isEmpty())
            <x-empty-state>
                @if ($search !== '' || $status || $overdue !== null)
                    Tidak ada tiket yang cocok dengan pencarian ini. <a href="{{ route('tickets.index') }}" class="text-decoration-underline fw-semibold">Tampilkan semua tiket</a>.
                @else
                    Belum ada tiket. <a href="{{ route('tickets.create') }}" class="text-decoration-underline fw-semibold">Buat tiket pertama</a>.
                @endif
            </x-empty-state>
        @else
            <!-- Tampilan Desktop (Table View) -->
            <div class="d-none d-md-block table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th scope="col" style="min-width: 240px;">Judul</th>
                            <th scope="col">Pelapor</th>
                            <th scope="col">Prioritas</th>
                            <th scope="col">Status</th>
                            <th scope="col" style="min-width: 170px;">Batas waktu</th>
                            <th scope="col" style="min-width: 160px;">Penanda</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $t)
                            @php
                                $late = (bool) $t->is_overdue;
                                $attCount = $t->attachments_count ?? $t->attachments->count();
                            @endphp
                            <tr class="{{ $late ? 'table-late' : '' }}">
                                <td>
                                    <a class="fw-semibold text-decoration-none text-body-emphasis d-block" href="{{ route('tickets.show', $t) }}">
                                        {{ $t->title }}
                                    </a>
                                    <div class="small text-muted d-flex align-items-center gap-2 mt-1">
                                        <span>#{{ $t->id }}</span>
                                        @if ($attCount > 0)
                                            <span class="badge text-bg-light border text-muted" title="{{ $attCount }} lampiran">
                                                📎 {{ $attCount }} file
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-secondary small">{{ $t->reporter_name }}</td>
                                <td>
                                    <x-priority-badge :priority="$t->priority" />
                                </td>
                                <td>
                                    <x-status-badge :status="$t->status" />
                                </td>
                                <td class="small">
                                    {{ $t->due_at->translatedFormat('d M Y, H:i') }} WIB
                                </td>
                                <td>
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        @if ($late)
                                            <x-overdue-badge />
                                        @endif
                                        <span class="small {{ $late ? 'text-danger fw-semibold' : 'text-muted' }}">
                                            {{ $t->deadlineNote() }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Tampilan Mobile (Kartu Bertumpuk Tanpa Scroll Horizontal) -->
            <div class="d-md-none list-group list-group-flush">
                @foreach ($tickets as $t)
                    @php
                        $late = (bool) $t->is_overdue;
                        $attCount = $t->attachments_count ?? $t->attachments->count();
                    @endphp
                    <div class="list-group-item p-3 {{ $late ? 'card-late' : '' }}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <span class="badge text-bg-light border text-muted small">#{{ $t->id }}</span>
                            <div class="d-flex align-items-center gap-1">
                                <x-priority-badge :priority="$t->priority" />
                                <x-status-badge :status="$t->status" />
                            </div>
                        </div>

                        <h2 class="h6 mb-2">
                            <a class="fw-bold text-decoration-none text-body-emphasis" href="{{ route('tickets.show', $t) }}">
                                {{ $t->title }}
                            </a>
                        </h2>

                        <div class="small text-muted mb-2 d-flex flex-wrap align-items-center gap-2">
                            <span>Pelapor: <strong class="text-body">{{ $t->reporter_name }}</strong></span>
                            @if ($attCount > 0)
                                <span>&bull;</span>
                                <span class="badge text-bg-light border text-muted">📎 {{ $attCount }} file</span>
                            @endif
                        </div>

                        <div class="d-flex justify-content-between align-items-center small pt-2 border-top">
                            <span class="text-muted">{{ $t->due_at->translatedFormat('d M Y, H:i') }} WIB</span>
                            <div class="d-flex align-items-center gap-1">
                                @if ($late)
                                    <x-overdue-badge />
                                @endif
                                <span class="{{ $late ? 'text-danger fw-semibold' : 'text-muted' }}">
                                    {{ $t->deadlineNote() }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

    {{ $tickets->links('pagination.default') }}
</x-layouts.app>
