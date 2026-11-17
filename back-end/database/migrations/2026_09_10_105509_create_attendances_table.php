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
        Schema::create('attendances', function (Blueprint $table) {
    $table->id();

    $table->foreignId('employee_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->foreignId('office_location_id')
        ->constrained()
        ->restrictOnDelete();

    $table->date('attendance_date');

    $table->dateTime('check_in')->nullable();
    $table->decimal('check_in_latitude', 10, 7)->nullable();
    $table->decimal('check_in_longitude', 10, 7)->nullable();
    $table->decimal('check_in_distance', 10, 2)->nullable();

    $table->dateTime('check_out')->nullable();
    $table->decimal('check_out_latitude', 10, 7)->nullable();
    $table->decimal('check_out_longitude', 10, 7)->nullable();
    $table->decimal('check_out_distance', 10, 2)->nullable();

    $table->enum('status', [
        'present',
        'late',
        'absent',
        'incomplete'
    ])->default('present');

    $table->text('notes')->nullable();

    $table->timestamps();

    $table->unique(['employee_id', 'attendance_date']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
