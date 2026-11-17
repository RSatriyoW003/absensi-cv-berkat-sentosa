<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'message' => 'Data employee tidak ditemukan.',
            ], 404);
        }

        $today = Carbon::today();

        $todayAttendance = Attendance::with('officeLocation')
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $weeklyAttendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [
                $startOfWeek->toDateString(),
                $endOfWeek->toDateString(),
            ])
            ->count();

        $todayStatus = 'not_working';
        if ($todayAttendance) {
            if ($todayAttendance->status === 'leave') {
                $todayStatus = 'leave';
            } elseif ($todayAttendance->status === 'sick') {
                $todayStatus = 'sick';
            } elseif ($todayAttendance->status === 'absent') {
                $todayStatus = 'absent';
            } elseif ($todayAttendance->check_out) {
                $todayStatus = 'completed';
            } else {
                $todayStatus = 'working';
            }
        }

        return response()->json([
            'message' => 'Dashboard berhasil diambil.',

            'employee' => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'name' => $user->name,
                'email' => $user->email,
                'position' => $employee->position,
                'department' => $employee->department,
                'phone' => $employee->phone,
                'status' => $employee->status,
                'photo' => $employee->photo,
                'photo_url' => $employee->photo_url,
            ],

            'today' => [
                'status' => $todayStatus,
                'attendance_status' => $todayAttendance?->status,
                'notes' => $todayAttendance?->notes,
                'check_in' => $todayAttendance?->check_in,
                'check_out' => $todayAttendance?->check_out,

                'office' => $todayAttendance?->officeLocation
                    ? [
                        'id' => $todayAttendance->officeLocation->id,
                        'name' => $todayAttendance->officeLocation->name,
                    ]
                    : null,
            ],

            'weekly' => [
                'total_attendance' => $weeklyAttendance,
                'start_date' => $startOfWeek->toDateString(),
                'end_date' => $endOfWeek->toDateString(),
            ],
        ], 200);
    }
}