<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private array $apiHeaders = ['X-API-Key' => 'kunci-test'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ticket_api.key' => 'kunci-test']);
        Storage::fake('local');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Komputer lambat',
            'reporter_name' => 'Budi',
            'description' => 'Aplikasi sering hang saat membuka file excel.',
            'priority' => 'medium',
            'due_at' => now()->addDays(2)->toIso8601String(),
        ], $overrides);
    }

    public function test_tiket_bisa_dibuat_tanpa_lampiran_melalui_web_dan_api(): void
    {
        // 1. Web
        $webRes = $this->post('/tickets', [
            'title' => 'Tiket Web Tanpa Lampiran',
            'reporter_name' => 'Andi',
            'description' => 'Deskripsi tiket tanpa lampiran.',
            'priority' => 'low',
            'due_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ]);
        $webRes->assertRedirect();
        $this->assertDatabaseHas('tickets', ['title' => 'Tiket Web Tanpa Lampiran']);

        // 2. API JSON
        $apiRes = $this->postJson('/api/tickets', $this->validPayload(['title' => 'Tiket API JSON']), $this->apiHeaders);
        $apiRes->assertCreated()
            ->assertJsonPath('data.title', 'Tiket API JSON')
            ->assertJsonPath('data.attachments', []);

        // 3. API Multipart tanpa field attachments
        $multipartRes = $this->post('/api/tickets', $this->validPayload(['title' => 'Tiket API Multipart']), array_merge($this->apiHeaders, ['Accept' => 'application/json']));
        $multipartRes->assertCreated()
            ->assertJsonPath('data.title', 'Tiket API Multipart')
            ->assertJsonPath('data.attachments', []);
    }

    public function test_upload_pdf_dan_jpg_berhasil_pada_web(): void
    {
        $pdf = UploadedFile::fake()->create('dokumen.pdf', 1500, 'application/pdf');
        $jpg = UploadedFile::fake()->image('foto.jpg', 600, 600)->size(800);

        $res = $this->post('/tickets', [
            'title' => 'Layar monitor bergaris',
            'reporter_name' => 'Dewi',
            'description' => 'Monitor nomor 3 muncul garis horizontal.',
            'priority' => 'high',
            'due_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'attachments' => [$pdf, $jpg],
        ]);

        $ticket = Ticket::where('title', 'Layar monitor bergaris')->firstOrFail();
        $res->assertRedirect(route('tickets.show', $ticket));

        $this->assertCount(2, $ticket->attachments);

        foreach ($ticket->attachments as $att) {
            Storage::disk('local')->assertExists($att->stored_path);
            $this->assertNotEquals('dokumen.pdf', basename($att->stored_path));
            $this->assertNotEquals('foto.jpg', basename($att->stored_path));
        }
    }

    public function test_upload_pdf_dan_png_berhasil_pada_api(): void
    {
        $pdf = UploadedFile::fake()->create('panduan.pdf', 2000, 'application/pdf');
        $png = UploadedFile::fake()->image('screenshot.png', 800, 600)->size(1000);

        $payload = $this->validPayload([
            'title' => 'Error blue screen',
            'attachments' => [$pdf, $png],
        ]);

        $res = $this->post('/api/tickets', $payload, array_merge($this->apiHeaders, ['Accept' => 'application/json']));
        $res->assertCreated()
            ->assertJsonPath('data.title', 'Error blue screen')
            ->assertJsonCount(2, 'data.attachments');

        $ticketId = $res->json('data.id');
        $ticket = Ticket::with('attachments')->findOrFail($ticketId);

        $this->assertCount(2, $ticket->attachments);
        foreach ($ticket->attachments as $att) {
            Storage::disk('local')->assertExists($att->stored_path);
        }
    }

    public function test_upload_berbagai_jenis_foto_png_webp_gif_berhasil(): void
    {
        $png = UploadedFile::fake()->image('diagram.png', 400, 300)->size(500);
        $webp = UploadedFile::fake()->create('foto.webp', 600, 'image/webp');
        $gif = UploadedFile::fake()->image('animasi.gif', 200, 200)->size(400);

        $payload = $this->validPayload([
            'title' => 'Uji berbagai jenis foto',
            'attachments' => [$png, $webp, $gif],
        ]);

        $res = $this->post('/api/tickets', $payload, array_merge($this->apiHeaders, ['Accept' => 'application/json']));
        $res->assertCreated()
            ->assertJsonCount(3, 'data.attachments');

        $ticket = Ticket::where('title', 'Uji berbagai jenis foto')->firstOrFail();
        $this->assertCount(3, $ticket->attachments);
    }

    public function test_file_exe_atau_txt_ditolak(): void
    {
        // 1. File .exe
        $exe = UploadedFile::fake()->create('virus.exe', 500, 'application/x-msdownload');
        $res1 = $this->post('/api/tickets', $this->validPayload(['attachments' => [$exe]]), array_merge($this->apiHeaders, ['Accept' => 'application/json']));
        $res1->assertStatus(422)
            ->assertJsonValidationErrors(['attachments.0']);

        // 2. File .txt
        $txt = UploadedFile::fake()->create('catatan.txt', 10, 'text/plain');
        $res2 = $this->post('/api/tickets', $this->validPayload(['attachments' => [$txt]]), array_merge($this->apiHeaders, ['Accept' => 'application/json']));
        $res2->assertStatus(422)
            ->assertJsonValidationErrors(['attachments.0']);
    }

    public function test_file_lebih_dari_5_mb_ditolak(): void
    {
        // 5121 KB > 5120 KB (5 MB)
        $largePdf = UploadedFile::fake()->create('buku_besar.pdf', 5121, 'application/pdf');

        $res = $this->post('/api/tickets', $this->validPayload(['attachments' => [$largePdf]]), array_merge($this->apiHeaders, ['Accept' => 'application/json']));
        $res->assertStatus(422)
            ->assertJsonValidationErrors(['attachments.0']);
    }

    public function test_lebih_dari_5_file_ditolak(): void
    {
        $files = [
            UploadedFile::fake()->create('f1.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('f2.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('f3.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('f4.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('f5.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('f6.pdf', 100, 'application/pdf'),
        ];

        $res = $this->post('/api/tickets', $this->validPayload(['attachments' => $files]), array_merge($this->apiHeaders, ['Accept' => 'application/json']));
        $res->assertStatus(422)
            ->assertJsonValidationErrors(['attachments']);
    }

    public function test_unduh_dan_lihat_lampiran_pada_web(): void
    {
        $ticket = $this->makeTicket();
        $fakePdf = UploadedFile::fake()->create('laporan.pdf', 500, 'application/pdf');
        $stored = Storage::disk('local')->putFileAs('attachments', $fakePdf, 'test-uuid.pdf');

        $attachment = $ticket->attachments()->create([
            'original_name' => 'laporan.pdf',
            'stored_path' => $stored,
            'mime_type' => 'application/pdf',
            'size' => 500 * 1024,
        ]);

        $res = $this->get(route('tickets.attachments.show', [$ticket, $attachment]));
        $res->assertOk();
        $res->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('inline', (string) $res->headers->get('Content-Disposition'));
    }

    public function test_unduh_lampiran_milik_tiket_lain_menghasilkan_404(): void
    {
        $ticket1 = $this->makeTicket(['title' => 'Tiket 1']);
        $ticket2 = $this->makeTicket(['title' => 'Tiket 2']);

        $fakePdf = UploadedFile::fake()->create('dok.pdf', 200, 'application/pdf');
        $stored = Storage::disk('local')->putFileAs('attachments', $fakePdf, 'dok1.pdf');

        $attachment1 = $ticket1->attachments()->create([
            'original_name' => 'dok.pdf',
            'stored_path' => $stored,
            'mime_type' => 'application/pdf',
            'size' => 200 * 1024,
        ]);

        // Coba akses attachment tiket 1 melalui URL tiket 2 di Web
        $webRes = $this->get(route('tickets.attachments.show', [$ticket2, $attachment1]));
        $webRes->assertNotFound();

        // Coba akses attachment tiket 1 melalui URL tiket 2 di API
        $apiRes = $this->getJson(route('api.tickets.attachments.show', [$ticket2, $attachment1]), $this->apiHeaders);
        $apiRes->assertStatus(404);
    }

    public function test_unduh_via_api_tanpa_key_ditolak_401(): void
    {
        $ticket = $this->makeTicket();
        $fakeJpg = UploadedFile::fake()->image('foto.jpg');
        $stored = Storage::disk('local')->putFileAs('attachments', $fakeJpg, 'img1.jpg');

        $attachment = $ticket->attachments()->create([
            'original_name' => 'foto.jpg',
            'stored_path' => $stored,
            'mime_type' => 'image/jpeg',
            'size' => 100 * 1024,
        ]);

        // Tanpa header X-API-Key
        $res = $this->getJson(route('api.tickets.attachments.show', [$ticket, $attachment]));
        $res->assertStatus(401);
    }

    public function test_tambah_lampiran_ke_tiket_yang_sudah_ada_via_api(): void
    {
        $ticket = $this->makeTicket();

        // Upload lampiran pertama
        $file1 = UploadedFile::fake()->create('berkas1.pdf', 300, 'application/pdf');
        $res1 = $this->post(
            route('api.tickets.attachments.store', $ticket),
            ['attachments' => [$file1]],
            array_merge($this->apiHeaders, ['Accept' => 'application/json'])
        );
        $res1->assertCreated()
            ->assertJsonCount(1, 'data.attachments');

        $this->assertEquals(1, $ticket->attachments()->count());

        // Coba upload 5 file lagi (1 + 5 = 6 > 5) -> harus ditolak 422
        $filesOverLimit = [
            UploadedFile::fake()->create('b1.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('b2.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('b3.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('b4.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('b5.pdf', 100, 'application/pdf'),
        ];
        $resOver = $this->post(
            route('api.tickets.attachments.store', $ticket),
            ['attachments' => $filesOverLimit],
            array_merge($this->apiHeaders, ['Accept' => 'application/json'])
        );
        $resOver->assertStatus(422)
            ->assertJsonValidationErrors(['attachments']);

        // Upload 4 file (total menjadi 5) -> sukses
        $filesFit = [
            UploadedFile::fake()->create('b1.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('b2.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('b3.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('b4.pdf', 100, 'application/pdf'),
        ];
        $resFit = $this->post(
            route('api.tickets.attachments.store', $ticket),
            ['attachments' => $filesFit],
            array_merge($this->apiHeaders, ['Accept' => 'application/json'])
        );
        $resFit->assertCreated()
            ->assertJsonCount(5, 'data.attachments');

        $this->assertEquals(5, $ticket->attachments()->count());
    }

    public function test_file_fisik_terhapus_saat_lampiran_atau_tiket_dihapus(): void
    {
        $ticket = $this->makeTicket();
        $fakePdf = UploadedFile::fake()->create('surat.pdf', 250, 'application/pdf');
        $stored = Storage::disk('local')->putFileAs('attachments', $fakePdf, 'uuid-surat.pdf');

        $attachment = $ticket->attachments()->create([
            'original_name' => 'surat.pdf',
            'stored_path' => $stored,
            'mime_type' => 'application/pdf',
            'size' => 250 * 1024,
        ]);

        Storage::disk('local')->assertExists($stored);

        // Hapus tiket -> file fisik lampiran harus ikut terhapus
        $ticket->delete();

        Storage::disk('local')->assertMissing($stored);
        $this->assertDatabaseMissing('ticket_attachments', ['id' => $attachment->id]);
    }
}
