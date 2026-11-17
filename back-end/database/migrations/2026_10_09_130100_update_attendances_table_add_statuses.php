<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ubah tipe status menjadi varchar agar mendukung 'present', 'late', 'absent', 'leave', 'sick', 'incomplete'
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'present'");
        
        // Buat office_location_id menjadi nullable untuk izin/sakit/alpa manual tanpa koordinat
        DB::statement("ALTER TABLE attendances MODIFY COLUMN office_location_id BIGINT UNSIGNED NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present', 'late', 'absent', 'incomplete') NOT NULL DEFAULT 'present'");
    }
};
