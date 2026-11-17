<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    public function test_user_can_login_via_api(): void
    {
        $user = User::where('email', 'admin@berkatsentosa.test')->first();
        if ($user) {
            $user->update(['password' => \Illuminate\Support\Facades\Hash::make('password')]);
        }

        $response = $this->postJson('/api/login', [
            'email' => 'admin@berkatsentosa.test',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email', 'role'],
            ]);
    }

    public function test_user_can_logout_and_revoke_token(): void
    {
        $user = User::where('email', 'admin@berkatsentosa.test')->first();
        $token = $user->createToken('test-logout-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logout berhasil.',
            ]);
    }

    public function test_admin_can_access_dashboard_stats(): void
    {
        $user = User::where('email', 'admin@berkatsentosa.test')->first();
        $token = $user->createToken('test-stats-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/admin/dashboard-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'stats' => [
                    'date',
                    'total_employees',
                    'total_offices',
                    'today_present',
                    'today_late',
                    'today_completed',
                    'today_not_checked_in',
                ],
            ]);

        $user->tokens()->where('name', 'test-stats-token')->delete();
    }

    public function test_admin_can_get_attendance_report(): void
    {
        $user = User::where('email', 'admin@berkatsentosa.test')->first();
        $token = $user->createToken('test-report-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/admin/attendances');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'attendances',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $user->tokens()->where('name', 'test-report-token')->delete();
    }

    public function test_admin_can_create_and_delete_office(): void
    {
        $user = User::where('email', 'admin@berkatsentosa.test')->first();
        $token = $user->createToken('test-office-token')->plainTextToken;

        $createRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/admin/offices', [
            'name' => 'Kantor Uji Coba Hapus',
            'latitude' => -7.25,
            'longitude' => 112.75,
            'radius' => 100,
        ]);

        $createRes->assertStatus(201);
        $officeId = $createRes->json('office.id');

        $deleteRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->deleteJson("/api/admin/offices/{$officeId}");

        $deleteRes->assertStatus(200);

        $user->tokens()->where('name', 'test-office-token')->delete();
    }

    public function test_can_register_new_admin(): void
    {
        $testEmail = 'admin.baru.' . time() . '@berkatsentosa.test';

        $res = $this->postJson('/api/register-admin', [
            'name' => 'Admin Baru',
            'email' => $testEmail,
            'password' => 'password123',
        ]);

        $res->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email', 'role'],
            ]);

        $this->assertEquals('admin', $res->json('user.role'));
    }
}
