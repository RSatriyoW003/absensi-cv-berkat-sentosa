<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    /**
     * Menampilkan daftar seluruh akun Administrator.
     */
    public function index()
    {
        $admins = User::where('role', 'admin')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'message' => 'Daftar administrator berhasil diambil.',
            'admins' => $admins,
        ], 200);
    }

    /**
     * Ringkasan seluruh akun (admin dan karyawan).
     */
    public function overview()
    {
        $totalAdmins = User::where('role', 'admin')->count();
        $totalEmployees = Employee::count();
        $totalUsers = User::count();

        return response()->json([
            'message' => 'Overview akun berhasil diambil.',
            'overview' => [
                'total_admins' => $totalAdmins,
                'total_employees' => $totalEmployees,
                'total_users' => $totalUsers,
            ],
        ], 200);
    }

    /**
     * Menambahkan akun Administrator baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $admin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
        ]);

        return response()->json([
            'message' => 'Akun administrator baru berhasil dibuat.',
            'admin' => $admin,
        ], 201);
    }

    /**
     * Memperbarui akun Administrator.
     */
    public function update(Request $request, User $user)
    {
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'User ini bukan administrator.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
        ]);

        $updateData = [];

        if (array_key_exists('name', $validated)) {
            $updateData['name'] = $validated['name'];
        }

        if (array_key_exists('email', $validated)) {
            $updateData['email'] = $validated['email'];
        }

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        return response()->json([
            'message' => 'Data administrator berhasil diperbarui.',
            'admin' => $user->fresh(),
        ], 200);
    }

    /**
     * Menghapus akun Administrator.
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'User ini bukan administrator.',
            ], 422);
        }

        // Cegah menghapus akun yang sedang login
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.',
            ], 403);
        }

        // Cegah menghapus jika hanya tersisa 1 admin
        $adminCount = User::where('role', 'admin')->count();
        if ($adminCount <= 1) {
            return response()->json([
                'message' => 'Tidak dapat menghapus akun administrator terakhir sistem.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'Akun administrator berhasil dihapus.',
        ], 200);
    }
}
