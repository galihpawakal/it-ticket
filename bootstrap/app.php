<?php

use App\Http\Middleware\EnsureValidApiKey;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.key' => EnsureValidApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Penanganan ukuran unggahan melebihi post_max_size PHP
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            $msg = 'Ukuran unggahan melebihi batas maksimal server (post_max_size). Silakan kurangi ukuran atau jumlah file.';
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->withErrors(['attachments' => $msg]);
        });

        // 404 JSON yang jelas untuk API: tiket tidak ada vs lampiran tidak ada vs endpoint tidak ada.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $prev = $e->getPrevious();
                if ($prev instanceof ModelNotFoundException) {
                    if ($prev->getModel() === TicketAttachment::class) {
                        return response()->json(['message' => 'Lampiran tidak ditemukan.'], 404);
                    }

                    return response()->json(['message' => 'Tiket tidak ditemukan.'], 404);
                }

                // Jika ada custom message dari abort(404, ...)
                $msg = $e->getMessage();
                if ($msg !== '' && !str_starts_with($msg, 'The route ') && !str_starts_with($msg, 'No query results')) {
                    return response()->json(['message' => $msg], 404);
                }

                // Cek path spesifik attachments
                if (preg_match('#^api/tickets/\d+/attachments/\d+$#', $request->path()) === 1) {
                    return response()->json(['message' => 'Lampiran tidak ditemukan.'], 404);
                }

                $isTicketPath = preg_match('#^api/tickets/[^/]+(/status|/attachments)?$#', $request->path()) === 1;
                $message = $isTicketPath ? 'Tiket tidak ditemukan.' : 'Endpoint tidak ditemukan.';

                return response()->json(['message' => $message], 404);
            }
        });
    })->create();
