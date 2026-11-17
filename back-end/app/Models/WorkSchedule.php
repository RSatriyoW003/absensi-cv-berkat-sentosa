<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    protected $fillable = [
        'name',
        'check_in_start',
        'work_start_time',
        'late_tolerance_time',
        'check_in_end',
        'work_end_time',
        'check_out_end',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Ambil konfigurasi jam kerja aktif, atau buat default jika belum ada.
     */
    public static function getActiveSchedule(): self
    {
        $schedule = static::where('is_active', true)->first();

        if (!$schedule) {
            $schedule = static::first();
        }

        if (!$schedule) {
            $schedule = static::create([
                'name' => 'Jam Kerja Reguler',
                'check_in_start' => '06:00:00',
                'work_start_time' => '08:00:00',
                'late_tolerance_time' => '08:15:00',
                'check_in_end' => '12:00:00',
                'work_end_time' => '17:00:00',
                'check_out_end' => '22:00:00',
                'is_active' => true,
            ]);
        }

        return $schedule;
    }
}
