<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;

class WorkScheduleController extends Controller
{
    /**
     * Menampilkan pengaturan jam kerja presensi aktif.
     */
    public function show()
    {
        $schedule = WorkSchedule::getActiveSchedule();

        return response()->json([
            'message' => 'Pengaturan jam kerja berhasil diambil.',
            'schedule' => $schedule,
        ], 200);
    }

    /**
     * Memperbarui pengaturan jam kerja presensi.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'check_in_start' => 'required|date_format:H:i,H:i:s',
            'work_start_time' => 'required|date_format:H:i,H:i:s',
            'late_tolerance_time' => 'required|date_format:H:i,H:i:s',
            'check_in_end' => 'required|date_format:H:i,H:i:s',
            'work_end_time' => 'required|date_format:H:i,H:i:s',
            'check_out_end' => 'required|date_format:H:i,H:i:s',
            'is_active' => 'nullable|boolean',
        ]);

        // Standarisasi format waktu ke H:i:s
        $timeFields = [
            'check_in_start',
            'work_start_time',
            'late_tolerance_time',
            'check_in_end',
            'work_end_time',
            'check_out_end',
        ];

        foreach ($timeFields as $field) {
            if (isset($validated[$field]) && strlen($validated[$field]) === 5) {
                $validated[$field] .= ':00';
            }
        }

        // Validasi logika waktu
        if ($validated['work_start_time'] < $validated['check_in_start']) {
            return response()->json([
                'message' => 'Jam masuk kerja tidak boleh lebih awal dari jam mulai dibuka check-in.',
            ], 422);
        }

        if ($validated['late_tolerance_time'] < $validated['work_start_time']) {
            return response()->json([
                'message' => 'Batas toleransi keterlambatan tidak boleh lebih awal dari jam masuk kerja.',
            ], 422);
        }

        if ($validated['check_in_end'] < $validated['late_tolerance_time']) {
            return response()->json([
                'message' => 'Batas akhir check-in tidak boleh lebih awal dari batas toleransi keterlambatan.',
            ], 422);
        }

        $schedule = WorkSchedule::getActiveSchedule();
        $schedule->update($validated);

        return response()->json([
            'message' => 'Pengaturan jam kerja presensi berhasil diperbarui.',
            'schedule' => $schedule->fresh(),
        ], 200);
    }
}
