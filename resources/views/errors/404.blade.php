<x-layouts.app title="404 — Halaman Tidak Ditemukan">
    <div class="row justify-content-center py-5">
        <div class="col-12 col-md-8 col-lg-6 text-center">
            <x-card class="py-5 px-4 shadow-sm">
                <div class="display-1 fw-bold text-muted mb-3 opacity-25">404</div>
                <h1 class="h4 fw-bold mb-3">Halaman atau Tiket Tidak Ditemukan</h1>
                <p class="text-muted mb-4">
                    Maaf, alamat halaman atau tiket yang Anda tuju tidak ditemukan di server atau telah dipindahkan.
                </p>
                <div>
                    <x-button :href="route('tickets.index')" variant="primary">
                        &larr; Kembali ke Daftar Tiket
                    </x-button>
                </div>
            </x-card>
        </div>
    </div>
</x-layouts.app>
