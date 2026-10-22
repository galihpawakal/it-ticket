<x-layouts.app title="Buat tiket">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <x-page-header title="Buat tiket baru">
                <x-slot:actions>
                    <x-button :href="route('tickets.index')" variant="outline" size="sm">
                        &larr; Kembali
                    </x-button>
                </x-slot:actions>
            </x-page-header>

            @if ($errors->any())
                <x-alert type="danger" :dismissible="true">
                    <strong class="d-block mb-1">Tiket belum tersimpan. Perbaiki {{ $errors->count() }} isian berikut:</strong>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <x-card>
                <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" novalidate>
                    @csrf

                    <x-form.field label="Judul" name="title" :required="true">
                        <x-form.input name="title" maxlength="150"
                                      placeholder="Contoh: Printer lantai 3 tidak bisa mencetak" required />
                    </x-form.field>

                    <x-form.field label="Nama pelapor" name="reporter_name" :required="true">
                        <x-form.input name="reporter_name" maxlength="100"
                                      placeholder="Nama lengkap atau divisi pelapor" required />
                    </x-form.field>

                    <x-form.field label="Deskripsi masalah" name="description" :required="true">
                        <x-form.textarea name="description" maxlength="5000" rows="4"
                                         placeholder="Apa yang terjadi, sejak kapan, dan apa dampaknya?" required />
                    </x-form.field>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <x-form.field label="Prioritas" name="priority" :required="true">
                                <x-form.select name="priority" required>
                                    @foreach ($priorities as $p)
                                        <option value="{{ $p->value }}" @selected(old('priority', 'medium') === $p->value)>
                                            {{ $p->label() }}
                                        </option>
                                    @endforeach
                                </x-form.select>
                            </x-form.field>
                        </div>
                        <div class="col-12 col-md-6">
                            <x-form.field label="Batas waktu penyelesaian (WIB)" name="due_at" :required="true"
                                          hint="Jam mengikuti Waktu Indonesia Barat (Asia/Jakarta).">
                                <x-form.input type="datetime-local" name="due_at" min="{{ $minDue }}" required />
                            </x-form.field>
                        </div>
                    </div>

                    <x-form.field label="Lampiran (opsional)" name="attachments"
                                  hint="Opsional. Maksimal 5 file per tiket. Format yang diizinkan: PDF (.pdf) atau foto (.jpg, .jpeg, .png, .webp, .gif, .bmp). Maksimal 5 MB per file.">
                        <x-form.input type="file" name="attachments[]" id="attachments" multiple
                                      accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.bmp,application/pdf,image/*" />
                    </x-form.field>

                    <div class="d-flex align-items-center gap-2 pt-3 border-top mt-4">
                        <x-button type="submit" variant="primary">
                            Simpan tiket
                        </x-button>
                        <x-button :href="route('tickets.index')" variant="outline">
                            Batal
                        </x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-layouts.app>
