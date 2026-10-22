<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('reporter_name', 100);
            $table->text('description');
            $table->string('priority', 10)->default('medium'); // low | medium | high
            $table->string('status', 20)->default('open');     // open | in_progress | done
            $table->dateTime('due_at');                         // disimpan dalam WIB (Asia/Jakarta)
            $table->dateTime('done_at')->nullable();            // terisi saat status done
            $table->dateTime('overdue_marked_at')->nullable();  // diisi scheduler saat pertama kali terdeteksi terlambat
            $table->timestamps();

            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
