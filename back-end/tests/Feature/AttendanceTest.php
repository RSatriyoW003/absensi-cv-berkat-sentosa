<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    public function test_employee_can_fetch_active_offices(): void
    {
        $user = User::where('email', 'budi@berkatsentosa.test')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'Budi Test',
                'email' => 'budi@berkatsentosa.test',
                'password' => Hash::make('password'),
                'role' => 'employee',
            ]);
        }

        $token = $user->createToken('test-employee-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/offices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'offices' => [
                    '*' => ['id', 'name', 'latitude', 'longitude', 'radius', 'status']
                ]
            ]);

        $user->tokens()->where('name', 'test-employee-token')->delete();
    }

    public function test_employee_check_in_out_of_radius_returns_422_with_details(): void
    {
        $user = User::where('email', 'budi@berkatsentosa.test')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'Budi Test',
                'email' => 'budi@berkatsentosa.test',
                'password' => Hash::make('password'),
                'role' => 'employee',
            ]);
        }

        if (!$user->employee) {
            Employee::create([
                'user_id' => $user->id,
                'employee_number' => 'EMP-TEST-001',
                'position' => 'Tester',
                'department' => 'IT',
                'phone' => '08123456789',
                'status' => 'active',
            ]);
        }

        $office = OfficeLocation::where('status', 'active')->first();
        $this->assertNotNull($office);

        $token = $user->createToken('test-employee-token')->plainTextToken;

        // Coordinates very far away (e.g. Jakarta vs Surabaya)
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/attendance/check-in', [
            'latitude' => -6.2000,
            'longitude' => 106.8166,
            'office_location_id' => $office->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'office',
                'distance',
                'radius',
            ]);

        $this->assertEquals($office->name, $response->json('office'));

        $user->tokens()->where('name', 'test-employee-token')->delete();
    }

    public function test_employee_can_request_leave_and_it_is_detected(): void
    {
        $user = User::where('email', 'budi@berkatsentosa.test')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'Budi Test',
                'email' => 'budi@berkatsentosa.test',
                'password' => Hash::make('password'),
                'role' => 'employee',
            ]);
        }

        if (!$user->employee) {
            Employee::create([
                'user_id' => $user->id,
                'employee_number' => 'EMP-TEST-001',
                'position' => 'Tester',
                'department' => 'IT',
                'phone' => '08123456789',
                'status' => 'active',
            ]);
            $user->refresh();
        }

        $token = $user->createToken('test-employee-token')->plainTextToken;

        $testDate = '2026-11-20';
        \App\Models\Attendance::where('employee_id', $user->employee->id)->where('attendance_date', $testDate)->delete();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/attendance/leave', [
            'attendance_date' => $testDate,
            'status' => 'leave',
            'notes' => 'Izin keperluan keluarga mendesak',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'attendance' => [
                    'status' => 'leave',
                    'notes' => 'Izin keperluan keluarga mendesak',
                ]
            ]);

        $historyRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/attendance/history');

        $historyRes->assertStatus(200);
        $attendanceList = $historyRes->json('attendance');
        $this->assertNotEmpty($attendanceList);
        $found = collect($attendanceList)->firstWhere('id', $response->json('attendance.id'));
        $this->assertNotNull($found);
        $this->assertEquals('leave', $found['status']);
        $this->assertEquals('Izin keperluan keluarga mendesak', $found['notes']);

        // Clean up test future date
        \App\Models\Attendance::where('id', $response->json('attendance.id'))->delete();

        // 3. Test submitting leave for TODAY and verify dashboard detection
        $todayDate = now()->toDateString();
        \App\Models\Attendance::where('employee_id', $user->employee->id)->where('attendance_date', $todayDate)->delete();

        $todayLeaveRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/attendance/leave', [
            'attendance_date' => $todayDate,
            'status' => 'leave',
            'notes' => 'Izin tidak masuk kerja hari ini',
        ]);

        $todayLeaveRes->assertStatus(201);

        // 4. Verify dashboard detects leave for today
        $dashRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/dashboard');

        $dashRes->assertStatus(200);
        $this->assertEquals('leave', $dashRes->json('today.status'));
        $this->assertEquals('Izin tidak masuk kerja hari ini', $dashRes->json('today.notes'));

        // 5. Verify check-in is blocked because employee is on leave
        $checkInRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/attendance/check-in', [
            'latitude' => -7.2575,
            'longitude' => 112.7521,
        ]);

        $checkInRes->assertStatus(409);
        $this->assertStringContainsString('Izin Tidak Masuk Kerja', $checkInRes->json('message'));

        // 6. Verify admin stats detects this employee's leave
        $adminUser = User::where('role', 'admin')->first();
        if ($adminUser) {
            \Laravel\Sanctum\Sanctum::actingAs($adminUser);

            $statsRes = $this->getJson('/api/admin/dashboard-stats');

            $statsRes->assertStatus(200);
            $this->assertGreaterThanOrEqual(1, $statsRes->json('stats.today_leave'));

            // 7. Verify admin attendance list shows employee leave with reason
            $adminListRes = $this->getJson('/api/admin/attendances?status=leave');

            $adminListRes->assertStatus(200);
            $adminFound = collect($adminListRes->json('attendances'))->firstWhere('employee_id', $user->employee->id);
            $this->assertNotNull($adminFound);
            $this->assertEquals('leave', $adminFound['status']);
            $this->assertEquals('Izin tidak masuk kerja hari ini', $adminFound['notes']);
        }

        // Clean up
        \App\Models\Attendance::where('employee_id', $user->employee->id)->where('attendance_date', $todayDate)->delete();
        $user->tokens()->where('name', 'test-employee-token')->delete();
    }
}

