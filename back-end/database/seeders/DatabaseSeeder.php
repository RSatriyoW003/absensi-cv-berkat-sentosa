<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Attendance;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Admin Utama (Project Manager sesuai Capstone Project Charter)
        $admin = User::updateOrCreate(
            ['email' => 'admin@cbs.com'],
            [
                'name' => 'Dian Ayu Wardani',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Akun Admin kompatibilitas
        User::updateOrCreate(
            ['email' => 'admin@berkatsentosa.test'],
            [
                'name' => 'Dian Ayu Wardani (PM)',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );
        User::updateOrCreate(
            ['email' => 'admin@sgm.test'],
            [
                'name' => 'Admin CV Berkat Sentosa',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // 2. Lokasi Kantor Tunggal CV Berkat Sentosa
        $office = OfficeLocation::first();
        if ($office) {
            $office->update([
                'name' => 'Kantor CV Berkat Sentosa (Pusat)',
                'latitude' => -7.2575000,
                'longitude' => 112.7521000,
                'radius' => 100,
                'status' => 'active',
            ]);
        } else {
            $office = OfficeLocation::create([
                'name' => 'Kantor CV Berkat Sentosa (Pusat)',
                'latitude' => -7.2575000,
                'longitude' => 112.7521000,
                'radius' => 100,
                'status' => 'active',
            ]);
        }

        // 3. Jam Kerja Default
        $schedule = WorkSchedule::getActiveSchedule();

        // 4. Karyawan Contoh Nyata CV Berkat Sentosa
        $employeesData = [
            [
                'email' => 'budi@cbs.com',
                'alt_email' => 'budi@berkatsentosa.test',
                'name' => 'Budi Pratama',
                'nip' => 'CBS-001',
                'position' => 'Staff IT',
                'dept' => 'Teknologi Informasi',
                'phone' => '081234567890',
            ],
            [
                'email' => 'siti@cbs.com',
                'alt_email' => 'siti@berkatsentosa.test',
                'name' => 'Siti Rahmawati',
                'nip' => 'CBS-002',
                'position' => 'Staff Keuangan',
                'dept' => 'Keuangan & Akuntansi',
                'phone' => '081234567891',
            ],
            [
                'email' => 'ahmad@cbs.com',
                'alt_email' => 'ahmad@berkatsentosa.test',
                'name' => 'Ahmad Fauzi',
                'nip' => 'CBS-003',
                'position' => 'Staff Operasional',
                'dept' => 'Operasional Lapangan',
                'phone' => '081234567892',
            ],
            [
                'email' => 'dewi@cbs.com',
                'alt_email' => 'dewi@berkatsentosa.test',
                'name' => 'Dewi Lestari',
                'nip' => 'CBS-004',
                'position' => 'Staff Administrasi',
                'dept' => 'Umum & Personalia',
                'phone' => '081234567893',
            ],
            [
                'email' => 'rian@cbs.com',
                'alt_email' => 'rian@berkatsentosa.test',
                'name' => 'Rian Hidayat',
                'nip' => 'CBS-005',
                'position' => 'Staff Logistik',
                'dept' => 'Gudang & Pengiriman',
                'phone' => '081234567894',
            ],
        ];

        $createdEmployees = [];
        foreach ($employeesData as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role' => 'employee',
                ]
            );

            if (!empty($data['alt_email'])) {
                User::updateOrCreate(
                    ['email' => $data['alt_email']],
                    [
                        'name' => $data['name'],
                        'password' => Hash::make('password'),
                        'role' => 'employee',
                    ]
                );
            }

            $emp = Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_number' => $data['nip'],
                    'position' => $data['position'],
                    'department' => $data['dept'],
                    'phone' => $data['phone'],
                    'status' => 'active',
                ]
            );
            $createdEmployees[] = $emp;
        }

        // 5. Buat Riwayat Absensi 14 Hari Kerja Terakhir untuk Keperluan Analisis K-Means Nyata
        $today = Carbon::today();
        $daysCount = 14;

        for ($d = $daysCount; $d >= 1; $d--) {
            $date = $today->copy()->subDays($d);
            // Lewati hari Minggu
            if ($date->isSunday()) {
                continue;
            }

            $dateStr = $date->toDateString();

            foreach ($createdEmployees as $idx => $emp) {
                // Tentukan pola kehadiran realistis untuk setiap karyawan
                // Karyawan 0 (Budi) & Karyawan 3 (Dewi): Disiplin Tinggi (Hadir on-time, sesekali telat 5 mnt)
                // Karyawan 1 (Siti): Disiplin Sedang (Hadir, 3x telat 15-20 mnt, 1x izin resmi)
                // Karyawan 2 (Ahmad) & Karyawan 4 (Rian): Perlu Evaluasi (Sering telat 30-50 mnt, ada alpa)

                if (in_array($idx, [0, 3])) {
                    // Disiplin Tinggi
                    $isLate = ($d === 3 && $idx === 0);
                    $checkInHour = $isLate ? '08:18:00' : '07:45:00';
                    $status = $isLate ? 'late' : 'present';

                    Attendance::firstOrCreate(
                        ['employee_id' => $emp->id, 'attendance_date' => $dateStr],
                        [
                            'office_location_id' => $office->id,
                            'check_in' => "{$dateStr} {$checkInHour}",
                            'check_in_latitude' => -7.2575100,
                            'check_in_longitude' => 112.7521100,
                            'check_in_distance' => 18.5,
                            'check_out' => "{$dateStr} 17:05:00",
                            'check_out_latitude' => -7.2575100,
                            'check_out_longitude' => 112.7521100,
                            'check_out_distance' => 19.0,
                            'status' => $status,
                            'approval_status' => 'approved',
                        ]
                    );
                } elseif ($idx === 1) {
                    // Cukup Disiplin (Siti)
                    if ($d === 5) {
                        // Izin resmi disetujui
                        Attendance::firstOrCreate(
                            ['employee_id' => $emp->id, 'attendance_date' => $dateStr],
                            [
                                'office_location_id' => null,
                                'status' => 'leave',
                                'approval_status' => 'approved',
                                'notes' => 'Izin keperluan perpanjangan SIM keluarga',
                                'approved_by' => $admin->id,
                                'approved_at' => "{$dateStr} 07:30:00",
                            ]
                        );
                    } else {
                        $isLate = in_array($d, [2, 7, 10]);
                        $checkInHour = $isLate ? '08:24:00' : '07:55:00';
                        $status = $isLate ? 'late' : 'present';

                        Attendance::firstOrCreate(
                            ['employee_id' => $emp->id, 'attendance_date' => $dateStr],
                            [
                                'office_location_id' => $office->id,
                                'check_in' => "{$dateStr} {$checkInHour}",
                                'check_in_latitude' => -7.2575200,
                                'check_in_longitude' => 112.7521200,
                                'check_in_distance' => 24.2,
                                'check_out' => "{$dateStr} 17:00:00",
                                'check_out_latitude' => -7.2575200,
                                'check_out_longitude' => 112.7521200,
                                'check_out_distance' => 24.0,
                                'status' => $status,
                                'approval_status' => 'approved',
                            ]
                        );
                    }
                } else {
                    // Perlu Evaluasi (Ahmad & Rian)
                    if ($d === 4 && $idx === 2) {
                        // Alpa (Tidak Masuk Kerja tanpa keterangan)
                        Attendance::firstOrCreate(
                            ['employee_id' => $emp->id, 'attendance_date' => $dateStr],
                            [
                                'office_location_id' => null,
                                'status' => 'absent',
                                'approval_status' => 'approved',
                                'notes' => 'Tidak hadir tanpa keterangan (Alpa)',
                                'approved_by' => $admin->id,
                                'approved_at' => "{$dateStr} 12:00:00",
                            ]
                        );
                    } elseif ($d === 6 && $idx === 4) {
                        // Alpa (Rian)
                        Attendance::firstOrCreate(
                            ['employee_id' => $emp->id, 'attendance_date' => $dateStr],
                            [
                                'office_location_id' => null,
                                'status' => 'absent',
                                'approval_status' => 'approved',
                                'notes' => 'Alpa tanpa konfirmasi izin',
                                'approved_by' => $admin->id,
                                'approved_at' => "{$dateStr} 12:00:00",
                            ]
                        );
                    } else {
                        // Keterlambatan cukup signifikan
                        $isLate = in_array($d, [1, 2, 7, 8, 11]);
                        $checkInHour = $isLate ? '08:42:00' : '08:02:00';
                        $status = $isLate ? 'late' : 'present';

                        Attendance::firstOrCreate(
                            ['employee_id' => $emp->id, 'attendance_date' => $dateStr],
                            [
                                'office_location_id' => $office->id,
                                'check_in' => "{$dateStr} {$checkInHour}",
                                'check_in_latitude' => -7.2575300,
                                'check_in_longitude' => 112.7521300,
                                'check_in_distance' => 31.0,
                                'check_out' => "{$dateStr} 17:15:00",
                                'check_out_latitude' => -7.2575300,
                                'check_out_longitude' => 112.7521300,
                                'check_out_distance' => 31.0,
                                'status' => $status,
                                'approval_status' => 'approved',
                            ]
                        );
                    }
                }
            }
        }

        // 6. Buat Contoh Pengajuan Izin Baru yang Pending & Rejected untuk Pengujian Alur Approval
        if (isset($createdEmployees[0])) {
            // Pengajuan Izin Pending untuk Budi
            Attendance::updateOrCreate(
                ['employee_id' => $createdEmployees[0]->id, 'attendance_date' => $today->copy()->addDay()->toDateString()],
                [
                    'office_location_id' => null,
                    'status' => 'leave',
                    'approval_status' => 'pending',
                    'notes' => 'Pengajuan izin menghadiri seminar teknologi universitas.',
                ]
            );
        }

        if (isset($createdEmployees[2])) {
            // Pengajuan Izin Ditolak untuk Ahmad
            Attendance::updateOrCreate(
                ['employee_id' => $createdEmployees[2]->id, 'attendance_date' => $today->copy()->subDays(2)->toDateString()],
                [
                    'office_location_id' => null,
                    'status' => 'leave',
                    'approval_status' => 'rejected',
                    'notes' => 'Izin mendadak urusan pribadi.',
                    'rejection_reason' => 'Pengajuan mendadak dan ada agenda penting operasional yang tidak dapat ditinggalkan.',
                    'approved_by' => $admin->id,
                    'approved_at' => $today->copy()->subDays(2)->format('Y-m-d 08:30:00'),
                ]
            );
        }
    }
}
