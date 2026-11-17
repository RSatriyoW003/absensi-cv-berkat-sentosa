<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Jam Kerja Normal');
            $table->time('check_in_start')->default('06:00:00');
            $table->time('work_start_time')->default('08:00:00');
            $table->time('late_tolerance_time')->default('08:15:00');
            $table->time('check_in_end')->default('12:00:00');
            $table->time('work_end_time')->default('17:00:00');
            $table->time('check_out_end')->default('22:00:00');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
