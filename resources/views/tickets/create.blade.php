@extends('layouts.app')
@section('title', 'Buat tiket')

@section('content')
    <h1>Buat tiket baru</h1>

    @if ($errors->any())
        <div class="errors" role="alert">
            <strong>Tiket belum tersimpan. Perbaiki {{ $errors->count() }} isian berikut:</strong>
            <ul>
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="panel">
        <form class="card" method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" novalidate>
            @csrf

            <div class="field {{ $errors->has('title') ? 'bad' : '' }}">
                <label for="title">Judul <span class="req">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" maxlength="150"
                       placeholder="Contoh: Printer lantai 3 tidak bisa mencetak" required
                       @if($errors->has('title')) aria-invalid="true" aria-describedby="title-err" @endif>
                @error('title') <div class="err" id="title-err">{{ $message }}</div> @enderror
            </div>

            <div class="field {{ $errors->has('reporter_name') ? 'bad' : '' }}">
                <label for="reporter_name">Nama pelapor <span class="req">*</span></label>
                <input type="text" id="reporter_name" name="reporter_name" value="{{ old('reporter_name') }}" maxlength="100" required
                       @if($errors->has('reporter_name')) aria-invalid="true" aria-describedby="reporter-err" @endif>
                @error('reporter_name') <div class="err" id="reporter-err">{{ $message }}</div> @enderror
            </div>

            <div class="field {{ $errors->has('description') ? 'bad' : '' }}">
                <label for="description">Deskripsi masalah <span class="req">*</span></label>
                <textarea id="description" name="description" maxlength="5000" required
                          placeholder="Apa yang terjadi, sejak kapan, dan apa dampaknya?"
                          @if($errors->has('description')) aria-invalid="true" aria-describedby="desc-err" @endif>{{ old('description') }}</textarea>
                @error('description') <div class="err" id="desc-err">{{ $message }}</div> @enderror
            </div>

            <div class="field {{ $errors->has('priority') ? 'bad' : '' }}">
                <label for="priority">Prioritas <span class="req">*</span></label>
                <select id="priority" name="priority" required
                        @if($errors->has('priority')) aria-invalid="true" aria-describedby="prio-err" @endif>
                    @foreach ($priorities as $p)
                        <option value="{{ $p->value }}" @selected(old('priority', 'medium') === $p->value)>{{ $p->label() }}</option>
                    @endforeach
                </select>
                @error('priority') <div class="err" id="prio-err">{{ $message }}</div> @enderror
            </div>

            <div class="field {{ $errors->has('due_at') ? 'bad' : '' }}">
                <label for="due_at">Batas waktu penyelesaian (WIB) <span class="req">*</span></label>
                <input type="datetime-local" id="due_at" name="due_at" value="{{ old('due_at') }}" min="{{ $minDue }}" required
                       @if($errors->has('due_at')) aria-invalid="true" aria-describedby="due-err" @endif>
                <div class="hint">Jam mengikuti Waktu Indonesia Barat (Asia/Jakarta).</div>
                @error('due_at') <div class="err" id="due-err">{{ $message }}</div> @enderror
            </div>

            <div class="field {{ $errors->has('attachments') || $errors->has('attachments.*') ? 'bad' : '' }}">
                <label for="attachments">Lampiran (opsional)</label>
                <input type="file" id="attachments" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.bmp,application/pdf,image/*"
                       @if($errors->has('attachments') || $errors->has('attachments.*')) aria-invalid="true" aria-describedby="attachments-err" @endif>
                <div class="hint">Opsional. Maksimal 5 file per tiket. Format yang diizinkan: PDF (.pdf) atau foto (.jpg, .jpeg, .png, .webp, .gif, .bmp). Maksimal 5 MB per file.</div>
                @error('attachments')
                    <div class="err" id="attachments-err">{{ $message }}</div>
                @enderror
                @foreach ($errors->get('attachments.*') as $key => $fileErrors)
                    @foreach ($fileErrors as $fileError)
                        <div class="err">{{ $fileError }}</div>
                    @endforeach
                @endforeach
            </div>

            <button class="btn primary" type="submit">Simpan tiket</button>
            <a class="btn" href="{{ route('tickets.index') }}">Batal</a>
        </form>
    </div>
@endsection
