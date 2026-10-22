<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->boolean('is_overdue')->default(false)->after('due_at');
            $table->index(['is_overdue', 'status']);
        });

        // Backfill data yang sudah ada sesuai aturan bisnis:
        // is_overdue = true JIKA status != 'done' DAN due_at < waktu sekarang (WIB)
        DB::table('tickets')
            ->where('status', '!=', 'done')
            ->where('due_at', '<', now())
            ->update(['is_overdue' => true]);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['is_overdue', 'status']);
            $table->dropColumn('is_overdue');
        });
    }
};
