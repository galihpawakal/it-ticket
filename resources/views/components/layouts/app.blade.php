@props(['title' => null])

<!DOCTYPE html>
<html lang="id" data-bs-theme="auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title . ' — ' : '' }}{{ config('app.name', 'Helpdesk IT') }}</title>

    <!-- Script Pendeteksi & Pengatur Tema (Light / Dark / Auto) -->
    <script>
        (() => {
            'use strict';
            const getStoredTheme = () => localStorage.getItem('theme');
            const getPreferredTheme = () => {
                const storedTheme = getStoredTheme();
                if (storedTheme) return storedTheme;
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            };
            const setTheme = theme => {
                if (theme === 'auto') {
                    document.documentElement.setAttribute('data-bs-theme', (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
                } else {
                    document.documentElement.setAttribute('data-bs-theme', theme);
                }
            };
            setTheme(getPreferredTheme());
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                const storedTheme = getStoredTheme();
                if (storedTheme !== 'light' && storedTheme !== 'dark') {
                    setTheme(getPreferredTheme());
                }
            });
            window.addEventListener('DOMContentLoaded', () => {
                const themeToggler = document.getElementById('theme-toggle-btn');
                if (!themeToggler) return;
                themeToggler.addEventListener('click', () => {
                    const currentTheme = document.documentElement.getAttribute('data-bs-theme');
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    localStorage.setItem('theme', newTheme);
                    setTheme(newTheme);
                    themeToggler.setAttribute('aria-label', `Ubah ke mode ${newTheme === 'dark' ? 'terang' : 'gelap'}`);
                });
            });
        })();
    </script>

    <!-- Bootstrap 5.3.3 CSS (Pinned via jsDelivr CDN dengan SRI) -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <!-- CSS Pelengkap Kustom -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-body-tertiary d-flex flex-column min-vh-100">
    <header>
        <nav class="navbar navbar-expand-lg bg-body border-bottom shadow-sm">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-decoration-none" href="{{ route('tickets.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="text-primary bi bi-ticket-detailed" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M4 5.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5Zm0 5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5Zm.5 2a.5.5 0 0 0 0 1h4a.5.5 0 0 0 0-1h-4Z"/>
                        <path d="M0 4.5A1.5 1.5 0 0 1 1.5 3h13A1.5 1.5 0 0 1 16 4.5V6a.5.5 0 0 1-.5.5 1.5 1.5 0 0 0 0 3 .5.5 0 0 1 .5.5v1.5a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 11.5V10a.5.5 0 0 1 .5-.5 1.5 1.5 0 1 0 0-3A.5.5 0 0 1 0 6V4.5ZM1.5 4a.5.5 0 0 0-.5.5v1.05a2.5 2.5 0 0 1 0 4.9v1.05a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-1.05a2.5 2.5 0 0 1 0-4.9V4.5a.5.5 0 0 0-.5-.5h-13Z"/>
                    </svg>
                    <span>Helpdesk IT</span>
                    <span class="badge text-bg-light border fw-normal text-muted ms-1" style="font-size: 0.72rem;">WIB</span>
                </a>

                <div class="d-flex align-items-center gap-2 ms-auto">
                    <!-- Tombol Pengubah Tema (Dark/Light Mode) -->
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2" id="theme-toggle-btn" aria-label="Ganti mode gelap/terang" title="Ganti mode gelap/terang">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-moon-stars" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278M4.858 1.311A7.27 7.27 0 0 0 1.025 7.71c0 4.02 3.279 7.276 7.319 7.276a7.32 7.32 0 0 0 5.205-2.162q-.506.063-1.029.063c-4.61 0-8.343-3.714-8.343-8.29 0-1.167.242-2.278.681-3.286"/>
                        </svg>
                    </button>

                    <a class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm" href="{{ route('tickets.create') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" class="bi bi-plus-lg me-1" viewBox="0 0 16 16" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/>
                        </svg>
                        Buat tiket
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-grow-1 py-4">
        <div class="container">
            @if (session('success'))
                <x-alert type="success" :dismissible="true">
                    {{ session('success') }}
                </x-alert>
            @endif

            {{ $slot }}
        </div>
    </main>

    <footer class="mt-auto py-3 bg-body border-top text-center text-muted small">
        <div class="container">
            <span>Helpdesk IT &bull; Zona Waktu Asia/Jakarta (WIB)</span>
        </div>
    </footer>

    <!-- Bootstrap 5.3.3 Bundle JS (Termasuk Popper, dipin via jsDelivr CDN dengan SRI) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
</body>
</html>
