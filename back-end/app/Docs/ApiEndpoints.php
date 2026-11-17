<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class ApiEndpoints
{
    // AUTHENTICATION

    #[OA\Post(path: '/api/login', tags: ['Authentication'], summary: 'Login pengguna', responses: [new OA\Response(response: 200, description: 'Login berhasil'), new OA\Response(response: 401, description: 'Login gagal')])]
    public function login() {}

    #[OA\Post(path: '/api/register-admin', tags: ['Authentication'], summary: 'Registrasi akun admin', responses: [new OA\Response(response: 201, description: 'Admin berhasil dibuat')])]
    public function registerAdmin() {}

    #[OA\Post(path: '/api/logout', tags: ['Authentication'], summary: 'Logout pengguna', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Logout berhasil')])]
    public function logout() {}

    // PROFILE & DASHBOARD

    #[OA\Get(path: '/api/profile', tags: ['Profile'], summary: 'Melihat profil pengguna', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Profil berhasil diambil')])]
    public function profile() {}

    #[OA\Post(path: '/api/profile', tags: ['Profile'], summary: 'Memperbarui profil pengguna', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Profil diperbarui')])]
    public function updateProfile() {}

    #[OA\Get(path: '/api/dashboard', tags: ['Dashboard'], summary: 'Menampilkan dashboard', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Data dashboard')])]
    public function dashboard() {}

    // ATTENDANCE

    #[OA\Get(path: '/api/attendance/attachment/{id}', tags: ['Attendance'], summary: 'Mengambil lampiran absensi', parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Lampiran berhasil diambil')])]
    public function attachment() {}

    #[OA\Get(path: '/api/work-schedule', tags: ['Attendance'], summary: 'Melihat jadwal kerja', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Jadwal kerja berhasil diambil')])]
    public function getSchedule() {}

    #[OA\Post(path: '/api/attendance/check-in', tags: ['Attendance'], summary: 'Melakukan absensi masuk', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Check-in berhasil')])]
    public function checkIn() {}

    #[OA\Post(path: '/api/attendance/check-out', tags: ['Attendance'], summary: 'Melakukan absensi pulang', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Check-out berhasil')])]
    public function checkOut() {}

    #[OA\Post(path: '/api/attendance/leave', tags: ['Attendance'], summary: 'Mengajukan izin atau sakit', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Pengajuan berhasil')])]
    public function requestLeave() {}

    #[OA\Get(path: '/api/attendance/history', tags: ['Attendance'], summary: 'Melihat riwayat absensi', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Riwayat absensi')])]
    public function history() {}

    // EMPLOYEE MANAGEMENT

    #[OA\Get(path: '/api/admin/employees', tags: ['Admin - Employees'], summary: 'Melihat daftar karyawan', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Daftar karyawan')])]
    public function employees() {}

    #[OA\Post(path: '/api/admin/employees', tags: ['Admin - Employees'], summary: 'Menambahkan karyawan', security: [['sanctum' => []]], responses: [new OA\Response(response: 201, description: 'Karyawan ditambahkan')])]
    public function storeEmployee() {}

    #[OA\Get(path: '/api/admin/employees/{employee}', tags: ['Admin - Employees'], summary: 'Melihat detail karyawan', parameters: [new OA\Parameter(name: 'employee', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Detail karyawan')])]
    public function showEmployee() {}

    #[OA\Put(path: '/api/admin/employees/{employee}', tags: ['Admin - Employees'], summary: 'Memperbarui data karyawan', parameters: [new OA\Parameter(name: 'employee', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Data diperbarui')])]
    public function updateEmployee() {}

    #[OA\Delete(path: '/api/admin/employees/{employee}', tags: ['Admin - Employees'], summary: 'Menghapus karyawan', parameters: [new OA\Parameter(name: 'employee', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Karyawan dihapus')])]
    public function deleteEmployee() {}

    // ADMIN ACCOUNTS

    #[OA\Get(path: '/api/admin/admins', tags: ['Admin - Accounts'], summary: 'Melihat daftar admin', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Daftar admin')])]
    public function admins() {}

    #[OA\Post(path: '/api/admin/admins', tags: ['Admin - Accounts'], summary: 'Menambahkan akun admin', security: [['sanctum' => []]], responses: [new OA\Response(response: 201, description: 'Admin ditambahkan')])]
    public function storeAdmin() {}

    #[OA\Put(path: '/api/admin/admins/{user}', tags: ['Admin - Accounts'], summary: 'Memperbarui akun admin', parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Admin diperbarui')])]
    public function updateAdmin() {}

    #[OA\Delete(path: '/api/admin/admins/{user}', tags: ['Admin - Accounts'], summary: 'Menghapus akun admin', parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Admin dihapus')])]
    public function deleteAdmin() {}

    #[OA\Get(path: '/api/admin/users-overview', tags: ['Admin - Accounts'], summary: 'Melihat ringkasan pengguna', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Ringkasan pengguna')])]
    public function usersOverview() {}

    // WORK SCHEDULE

    #[OA\Get(path: '/api/admin/work-schedule', tags: ['Admin - Work Schedule'], summary: 'Melihat pengaturan jam kerja', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Jadwal kerja')])]
    public function adminWorkSchedule() {}

    #[OA\Put(path: '/api/admin/work-schedule', tags: ['Admin - Work Schedule'], summary: 'Memperbarui pengaturan jam kerja', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Jadwal diperbarui')])]
    public function updateWorkSchedule() {}

    // OFFICE LOCATIONS

    #[OA\Get(path: '/api/offices', tags: ['Office Locations'], summary: 'Melihat lokasi kantor', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Daftar lokasi kantor')])]
    public function offices() {}

    #[OA\Get(path: '/api/admin/offices', tags: ['Admin - Office Locations'], summary: 'Melihat daftar lokasi kantor admin', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Daftar lokasi')])]
    public function adminOffices() {}

    #[OA\Get(path: '/api/admin/office', tags: ['Admin - Office Locations'], summary: 'Melihat lokasi kantor tunggal', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Lokasi kantor')])]
    public function adminOffice() {}

    #[OA\Post(path: '/api/admin/offices', tags: ['Admin - Office Locations'], summary: 'Menambahkan lokasi kantor', security: [['sanctum' => []]], responses: [new OA\Response(response: 201, description: 'Lokasi ditambahkan')])]
    public function storeOffice() {}

    #[OA\Put(path: '/api/admin/offices/{office}', tags: ['Admin - Office Locations'], summary: 'Memperbarui lokasi kantor', parameters: [new OA\Parameter(name: 'office', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Lokasi diperbarui')])]
    public function updateOffice() {}

    #[OA\Put(path: '/api/admin/office', tags: ['Admin - Office Locations'], summary: 'Memperbarui lokasi kantor tunggal', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Lokasi diperbarui')])]
    public function updateSingleOffice() {}

    // ATTENDANCE REPORT

    #[OA\Get(path: '/api/admin/attendances', tags: ['Admin - Attendance Reports'], summary: 'Melihat rekap absensi', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Rekap absensi')])]
    public function attendanceReport() {}

    #[OA\Post(path: '/api/admin/attendances/record-status', tags: ['Admin - Attendance Reports'], summary: 'Mencatat status absensi manual', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Status dicatat')])]
    public function recordStatus() {}

    #[OA\Get(path: '/api/admin/dashboard-stats', tags: ['Admin - Attendance Reports'], summary: 'Melihat statistik dashboard admin', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Statistik dashboard')])]
    public function dashboardStats() {}

    // LEAVE APPROVAL

    #[OA\Get(path: '/api/admin/leave-requests', tags: ['Admin - Leave Approval'], summary: 'Melihat permintaan izin', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Daftar permintaan izin')])]
    public function leaveRequests() {}

    #[OA\Post(path: '/api/admin/leave-requests/{attendance}/approve', tags: ['Admin - Leave Approval'], summary: 'Menyetujui permintaan izin', parameters: [new OA\Parameter(name: 'attendance', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Izin disetujui')])]
    public function approveLeave() {}

    #[OA\Post(path: '/api/admin/leave-requests/{attendance}/reject', tags: ['Admin - Leave Approval'], summary: 'Menolak permintaan izin', parameters: [new OA\Parameter(name: 'attendance', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Izin ditolak')])]
    public function rejectLeave() {}

    // K-MEANS

    #[OA\Get(path: '/api/admin/kmeans-clustering', tags: ['Admin - K-Means'], summary: 'Analisis clustering K-Means', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Hasil clustering')])]
    public function kmeans() {}
}
