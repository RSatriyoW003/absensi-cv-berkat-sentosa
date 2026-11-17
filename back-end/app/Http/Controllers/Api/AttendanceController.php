<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\OfficeLocation;
use App\Models\WorkSchedule;
use App\Services\LocationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function getSchedule()
    {
        $schedule = WorkSchedule::getActiveSchedule();

        return response()->json([
            'message' => 'Jadwal jam kerja presensi.',
            'schedule' => $schedule,
        ], 200);
    }

    /**
     * Presensi Masuk (Check-in) - Tervalidasi ke lokasi kantor tunggal CV Berkat Sentosa
     */
    public function checkIn(Request $request, LocationService $locationService)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'message' => 'Data employee tidak ditemukan.',
            ], 404);
        }

        $now = now();
        $schedule = WorkSchedule::getActiveSchedule();
        $currentTime = $now->format('H:i:s');

        // Cek apakah sudah tercatat izin / sakit / alpa / check-in hari ini
        $existing = Attendance::where('employee_id', $employee->id)
            ->where('attendance_date', $now->toDateString())
            ->first();

        if ($existing) {
            // Jika ada izin/sakit yang sudah disetujui
            if (in_array($existing->status, ['leave', 'sick']) && $existing->approval_status === 'approved') {
                $statusIndo = $existing->status === 'leave' ? 'Izin Tidak Masuk Kerja' : 'Sakit';
                $reasonInfo = $existing->notes ? " (Keterangan: {$existing->notes})" : '';
                return response()->json([
                    'message' => "Pengajuan {$statusIndo} Anda telah disetujui hari ini{$reasonInfo}. Anda tidak perlu melakukan presensi.",
                    'attendance' => $existing,
                ], 409);
            }

            // Jika ada pengajuan izin/sakit yang masih pending
            if (in_array($existing->status, ['leave', 'sick']) && $existing->approval_status === 'pending') {
                $statusIndo = $existing->status === 'leave' ? 'Izin' : 'Sakit';
                return response()->json([
                    'message' => "Pengajuan {$statusIndo} Anda untuk hari ini masih berstatus 'Menunggu Persetujuan Admin'.",
                    'attendance' => $existing,
                ], 409);
            }

            // Jika alpa resmi
            if ($existing->status === 'absent') {
                return response()->json([
                    'message' => 'Anda telah tercatat Alpa / Tidak Masuk Kerja hari ini. Silakan hubungi admin.',
                    'attendance' => $existing,
                ], 409);
            }

            // Jika sudah check-in reguler
            if ($existing->check_in) {
                return response()->json([
                    'message' => 'Anda sudah melakukan check-in hari ini.',
                    'attendance' => $existing,
                ], 409);
            }
        }

        // Cek jika presensi masuk belum dibuka
        if ($schedule->check_in_start && $currentTime < $schedule->check_in_start) {
            $startFormatted = substr($schedule->check_in_start, 0, 5);
            return response()->json([
                'message' => "Presensi masuk belum dibuka. Jam masuk dibuka mulai pukul {$startFormatted} WIB.",
            ], 422);
        }

        // Cek jika batas akhir presensi masuk sudah lewat (alpa / terlambat lewat batas)
        if ($schedule->check_in_end && $currentTime > $schedule->check_in_end) {
            $endFormatted = substr($schedule->check_in_end, 0, 5);
            return response()->json([
                'message' => "Batas akhir presensi masuk telah berakhir (pukul {$endFormatted} WIB). Anda tercatat tidak masuk / alpa hari ini. Silakan hubungi admin.",
            ], 422);
        }

        // Ambil lokasi kantor tunggal CV Berkat Sentosa
        $office = OfficeLocation::where('status', 'active')->first() ?: OfficeLocation::first();

        if (!$office) {
            return response()->json([
                'message' => 'Konfigurasi lokasi kantor CV Berkat Sentosa belum diatur. Hubungi administrator.',
            ], 404);
        }

        $distance = $locationService->calculateDistance(
            (float) $request->latitude,
            (float) $request->longitude,
            (float) $office->latitude,
            (float) $office->longitude
        );

        if ($distance > $office->radius) {
            $distText = $distance >= 1000 
                ? round($distance / 1000, 2) . ' km' 
                : round($distance) . ' meter';
            $radText = $office->radius >= 1000
                ? round($office->radius / 1000, 2) . ' km'
                : $office->radius . ' m';

            return response()->json([
                'message' => "Anda berada di luar radius kantor {$office->name}. Jarak Anda {$distText} (Maksimal radius: {$radText}).",
                'office' => $office->name,
                'distance' => round($distance, 1),
                'radius' => $office->radius,
            ], 422);
        }

        // Tentukan apakah masuk tepat waktu atau terlambat
        $toleranceTime = $schedule->late_tolerance_time ?: $schedule->work_start_time;
        $attendanceStatus = $currentTime > $toleranceTime ? 'late' : 'present';

        if ($existing && $existing->approval_status === 'rejected') {
            // Jika izin sebelumnya ditolak, timpa record menjadi kehadiran reguler
            $existing->update([
                'office_location_id' => $office->id,
                'check_in' => $now,
                'check_in_latitude' => $request->latitude,
                'check_in_longitude' => $request->longitude,
                'check_in_distance' => $distance,
                'status' => $attendanceStatus,
                'approval_status' => 'approved',
            ]);
            $attendance = $existing->fresh();
        } else {
            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'attendance_date' => $now->toDateString(),
                'office_location_id' => $office->id,
                'check_in' => $now,
                'check_in_latitude' => $request->latitude,
                'check_in_longitude' => $request->longitude,
                'check_in_distance' => $distance,
                'status' => $attendanceStatus,
                'approval_status' => 'approved',
            ]);
        }

        $statusLabel = $attendanceStatus === 'late' ? 'Terlambat' : 'Tepat Waktu';

        return response()->json([
            'message' => "Check-in berhasil ({$statusLabel}) di {$office->name}.",
            'attendance' => $attendance->load('officeLocation'),
            'office' => $office->name,
            'distance' => round($distance, 1),
        ], 201);
    }

    /**
     * Presensi Pulang (Check-out)
     */
    public function checkOut(Request $request, LocationService $locationService)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'message' => 'Data employee tidak ditemukan.',
            ], 404);
        }

        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('attendance_date', now()->toDateString())
            ->first();

        if (!$attendance || !$attendance->check_in) {
            return response()->json([
                'message' => 'Anda belum melakukan check-in hari ini.',
            ], 404);
        }

        if (in_array($attendance->status, ['leave', 'sick', 'absent'])) {
            $statusIndo = match ($attendance->status) {
                'leave' => 'Izin Tidak Masuk Kerja',
                'sick' => 'Sakit',
                'absent' => 'Alpa / Tidak Masuk Kerja',
                default => 'Izin'
            };
            return response()->json([
                'message' => "Anda tercatat berstatus {$statusIndo} hari ini sehingga tidak dapat melakukan check-out.",
            ], 422);
        }

        if ($attendance->check_out) {
            return response()->json([
                'message' => 'Anda sudah melakukan check-out hari ini.',
                'attendance' => $attendance,
            ], 409);
        }

        $office = OfficeLocation::find($attendance->office_location_id) 
            ?: (OfficeLocation::where('status', 'active')->first() ?: OfficeLocation::first());

        if (!$office) {
            return response()->json([
                'message' => 'Lokasi kantor CV Berkat Sentosa tidak ditemukan.',
            ], 404);
        }

        $distance = $locationService->calculateDistance(
            (float) $request->latitude,
            (float) $request->longitude,
            (float) $office->latitude,
            (float) $office->longitude
        );

        if ($distance > $office->radius) {
            $distText = $distance >= 1000 
                ? round($distance / 1000, 2) . ' km' 
                : round($distance) . ' meter';
            $radText = $office->radius >= 1000
                ? round($office->radius / 1000, 2) . ' km'
                : $office->radius . ' m';

            return response()->json([
                'message' => "Anda berada di luar radius kantor {$office->name}. Jarak Anda {$distText} (Maksimal: {$radText}).",
                'distance' => round($distance, 2),
                'radius' => $office->radius,
                'office' => $office->name,
            ], 422);
        }

        $attendance->update([
            'check_out' => now(),
            'check_out_latitude' => $request->latitude,
            'check_out_longitude' => $request->longitude,
            'check_out_distance' => $distance,
        ]);

        return response()->json([
            'message' => 'Check-out berhasil.',
            'attendance' => $attendance->fresh()->load('officeLocation'),
        ], 200);
    }

    /**
     * Pengajuan izin tidak masuk kerja oleh karyawan (Izin / Sakit)
     * Menggunakan alur persetujuan: status awal 'pending'
     */
    public function requestLeave(Request $request)
    {
        $request->validate([
            'attendance_date' => 'required|date',
            'status' => 'required|in:leave,sick',
            'notes' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf,doc,docx|max:5120',
        ], [
            'attachment.mimes' => 'Format berkas lampiran tidak didukung. Harap unggah format JPG, PNG, WEBP, PDF, atau DOCX.',
            'attachment.max' => 'Ukuran berkas lampiran maksimal 5 MB.',
        ]);

        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'message' => 'Data employee tidak ditemukan.',
            ], 404);
        }

        $attendanceDate = Carbon::parse($request->attendance_date)->toDateString();

        // Cek apakah sudah ada presensi pada tanggal tersebut
        $existing = Attendance::where('employee_id', $employee->id)
            ->where('attendance_date', $attendanceDate)
            ->first();

        if ($existing && $existing->check_in) {
            return response()->json([
                'message' => 'Anda sudah melakukan check-in pada tanggal tersebut, tidak dapat mengajukan izin tidak masuk kerja.',
            ], 422);
        }

        // Upload berkas lampiran jika disediakan
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');

            // Sinkronisasi otomatis ke public/storage/attachments agar langsung dapat diakses web
            try {
                $publicAttachmentsDir = public_path('storage/attachments');
                if (!is_dir($publicAttachmentsDir)) {
                    @mkdir($publicAttachmentsDir, 0755, true);
                }
                @copy(Storage::disk('public')->path($attachmentPath), public_path('storage/' . $attachmentPath));
            } catch (\Throwable $e) {
                // Abaikan kesalahan duplikasi
            }
        }

        $statusText = $request->status === 'leave' ? 'Izin' : 'Sakit';

        if ($existing) {
            // Hapus lampiran lama jika ada berkas baru
            if ($attachmentPath && $existing->attachment && Storage::disk('public')->exists($existing->attachment)) {
                Storage::disk('public')->delete($existing->attachment);
                @unlink(public_path('storage/' . $existing->attachment));
            }

            $updateData = [
                'status' => $request->status,
                'approval_status' => 'pending',
                'notes' => $request->notes,
                'rejection_reason' => null,
                'approved_by' => null,
                'approved_at' => null,
                'office_location_id' => null,
            ];

            if ($attachmentPath) {
                $updateData['attachment'] = $attachmentPath;
            }

            $existing->update($updateData);

            return response()->json([
                'message' => "Pengajuan {$statusText} untuk tanggal {$attendanceDate} berhasil diajukan dan sedang menunggu persetujuan admin.",
                'attendance' => $existing->fresh()->load('officeLocation'),
            ], 200);
        }

        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => $attendanceDate,
            'status' => $request->status,
            'approval_status' => 'pending',
            'notes' => $request->notes,
            'attachment' => $attachmentPath,
            'check_in' => null,
            'check_out' => null,
            'office_location_id' => null,
        ]);

        return response()->json([
            'message' => "Pengajuan {$statusText} untuk tanggal {$attendanceDate} berhasil diajukan dan sedang menunggu persetujuan admin.",
            'attendance' => $attendance->fresh()->load('officeLocation'),
        ], 201);
    }

    /**
     * Tampilkan atau unduh berkas lampiran bukti izin/sakit
     */
    public function getAttachment(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        if (!$attendance->attachment) {
            abort(404, 'Lampiran tidak ditemukan.');
        }

        $filePath = $attendance->attachment;
        $disk = Storage::disk('public');

        if (!$disk->exists($filePath)) {
            // Coba periksa di public/storage
            $directPublicPath = public_path('storage/' . ltrim($filePath, '/'));
            if (!file_exists($directPublicPath)) {
                abort(404, 'Berkas lampiran fisik tidak ditemukan di server.');
            }
            $fullPath = $directPublicPath;
        } else {
            $fullPath = $disk->path($filePath);
        }

        $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
        $fileName = basename($fullPath);

        // Jika dipanggil dengan ?download=1 atau format file doc/docx, unduh file
        $isDoc = in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), ['doc', 'docx']);
        if ($request->boolean('download') || $isDoc) {
            return response()->download($fullPath, $fileName, [
                'Content-Type' => $mimeType,
            ]);
        }

        // Tampilkan inline (untuk gambar dan PDF di browser)
        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Riwayat kehadiran karyawan
     */
    public function history(Request $request)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'message' => 'Data employee tidak ditemukan.',
            ], 404);
        }

        $attendances = Attendance::with(['officeLocation', 'approvedBy'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('attendance_date')
            ->get();

        return response()->json([
            'message' => 'Riwayat absensi berhasil diambil.',
            'attendance' => $attendances,
        ], 200);
    }
}