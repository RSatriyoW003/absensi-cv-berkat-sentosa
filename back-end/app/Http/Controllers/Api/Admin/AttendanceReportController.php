<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OfficeLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{
    /**
     * Menampilkan rekap data absensi dengan filter dan pagination.
     */
    public function index(Request $request)
    {
        $query = Attendance::with(['employee.user', 'officeLocation', 'approvedBy']);

        if ($request->filled('start_date')) {
            $query->whereDate('attendance_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('attendance_date', '<=', $request->end_date);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->filled('department')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('employee', function ($q) use ($searchTerm) {
                $q->where('employee_number', 'like', "%{$searchTerm}%")
                  ->orWhereHas('user', function ($uq) use ($searchTerm) {
                      $uq->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $attendances = $query->orderByDesc('attendance_date')
            ->orderByDesc('check_in')
            ->paginate($perPage);

        return response()->json([
            'message' => 'Rekap data absensi berhasil diambil.',
            'attendances' => $attendances->items(),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ], 200);
    }

    /**
     * Mengambil daftar seluruh pengajuan izin & sakit karyawan (Workflow Approval)
     */
    public function leaveRequests(Request $request)
    {
        $query = Attendance::with(['employee.user', 'approvedBy'])
            ->whereIn('status', ['leave', 'sick']);

        if ($request->filled('approval_status') && $request->approval_status !== 'all') {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('employee_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $requests = $query->orderByDesc('attendance_date')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'message' => 'Daftar pengajuan izin karyawan berhasil diambil.',
            'leave_requests' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ], 200);
    }

    /**
     * Menyetujui pengajuan izin karyawan
     */
    public function approveLeave(Request $request, Attendance $attendance)
    {
        if (!in_array($attendance->status, ['leave', 'sick'])) {
            return response()->json([
                'message' => 'Record absensi ini bukan merupakan pengajuan izin atau sakit.',
            ], 422);
        }

        $attendance->update([
            'approval_status' => 'approved',
            'rejection_reason' => null,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $statusLabel = $attendance->status === 'leave' ? 'Izin' : 'Sakit';

        return response()->json([
            'message' => "Pengajuan {$statusLabel} untuk karyawan {$attendance->employee->user->name} berhasil disetujui.",
            'attendance' => $attendance->fresh()->load(['employee.user', 'approvedBy']),
        ], 200);
    }

    /**
     * Menolak pengajuan izin karyawan dengan alasan penolakan
     */
    public function rejectLeave(Request $request, Attendance $attendance)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        if (!in_array($attendance->status, ['leave', 'sick'])) {
            return response()->json([
                'message' => 'Record absensi ini bukan merupakan pengajuan izin atau sakit.',
            ], 422);
        }

        $attendance->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $statusLabel = $attendance->status === 'leave' ? 'Izin' : 'Sakit';

        return response()->json([
            'message' => "Pengajuan {$statusLabel} telah ditolak. Alasan penolakan dicatat dan dikirimkan ke akun karyawan.",
            'attendance' => $attendance->fresh()->load(['employee.user', 'approvedBy']),
        ], 200);
    }

    /**
     * Catat status kehadiran manual oleh admin (Izin, Sakit, Alpa, Hadir, Terlambat).
     */
    public function recordManualStatus(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_date' => 'required|date',
            'status' => 'required|in:present,late,leave,sick,absent',
            'notes' => 'nullable|string|max:500',
        ]);

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $validated['employee_id'],
                'attendance_date' => $validated['attendance_date'],
            ],
            [
                'status' => $validated['status'],
                'approval_status' => 'approved',
                'notes' => $validated['notes'] ?? null,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Status kehadiran karyawan berhasil dicatat oleh administrator.',
            'attendance' => $attendance->fresh()->load(['employee.user', 'officeLocation', 'approvedBy']),
        ], 200);
    }

    /**
     * Statistik ringkasan kehadiran untuk dashboard admin.
     */
    public function stats(Request $request)
    {
        $today = Carbon::today();

        $totalActiveEmployees = Employee::where('status', 'active')->count();

        // Ambil lokasi kantor tunggal CV Berkat Sentosa
        $office = OfficeLocation::where('status', 'active')->first();

        $todayAttendances = Attendance::whereDate('attendance_date', $today)->get();
        $todayPresentCount = $todayAttendances->where('status', 'present')->count();
        $todayLateCount = $todayAttendances->where('status', 'late')->count();
        
        // Izin/Sakit hari ini hanya dihitung jika sudah di-approve
        $todayLeaveCount = $todayAttendances->where('status', 'leave')
            ->where('approval_status', 'approved')
            ->count();
        $todaySickCount = $todayAttendances->where('status', 'sick')
            ->where('approval_status', 'approved')
            ->count();

        $todayAbsentCount = $todayAttendances->where('status', 'absent')->count();
        $todayCompletedCount = $todayAttendances->whereNotNull('check_out')->count();

        // Hitung pengajuan izin yang berstatus pending (menunggu persetujuan admin)
        $totalPendingLeaves = Attendance::whereIn('status', ['leave', 'sick'])
            ->where('approval_status', 'pending')
            ->count();

        $todayRecordedEmployeeIds = $todayAttendances->pluck('employee_id')->toArray();
        $todayNotCheckedInCount = Employee::where('status', 'active')
            ->whereNotIn('id', $todayRecordedEmployeeIds)
            ->count();

        return response()->json([
            'message' => 'Statistik dashboard admin berhasil diambil.',
            'stats' => [
                'date' => $today->toDateString(),
                'company' => 'CV Berkat Sentosa',
                'office_name' => $office ? $office->name : 'Kantor CV Berkat Sentosa (Pusat)',
                'total_employees' => $totalActiveEmployees,
                'total_pending_leaves' => $totalPendingLeaves,
                'today_present' => $todayPresentCount,
                'today_late' => $todayLateCount,
                'today_leave' => $todayLeaveCount,
                'today_sick' => $todaySickCount,
                'today_absent' => $todayAbsentCount,
                'today_completed' => $todayCompletedCount,
                'today_not_checked_in' => $todayNotCheckedInCount,
            ],
        ], 200);
    }
}
