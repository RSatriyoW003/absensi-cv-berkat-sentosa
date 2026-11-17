<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * Menampilkan seluruh karyawan.
     */
    public function index()
    {
        $employees = Employee::with('user')
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Daftar karyawan berhasil diambil.',
            'employees' => $employees,
        ], 200);
    }

    /**
     * Menambahkan karyawan baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'employee_number' => 'required|string|max:255|unique:employees,employee_number',
            'position' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $employee = DB::transaction(function () use ($validated) {

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'employee',
            ]);

            return Employee::create([
                'user_id' => $user->id,
                'employee_number' => $validated['employee_number'],
                'position' => $validated['position'],
                'department' => $validated['department'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'status' => 'active',
            ]);
        });

        return response()->json([
            'message' => 'Karyawan berhasil ditambahkan.',
            'employee' => $employee->load('user'),
        ], 201);
    }

    /**
     * Menampilkan detail satu karyawan.
     */
    public function show(Employee $employee)
    {
        $employee->load('user');

        return response()->json([
            'message' => 'Detail karyawan berhasil diambil.',
            'employee' => $employee,
        ], 200);
    }

    /**
     * Memperbarui data karyawan.
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $employee->user_id,
            'password' => 'nullable|string|min:6',
            'employee_number' => 'sometimes|required|string|max:255|unique:employees,employee_number,' . $employee->id,
            'position' => 'sometimes|required|string|max:255',
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        DB::transaction(function () use ($validated, $employee) {

            $userData = [];

            if (array_key_exists('name', $validated)) {
                $userData['name'] = $validated['name'];
            }

            if (array_key_exists('email', $validated)) {
                $userData['email'] = $validated['email'];
            }

            if (!empty($validated['password'])) {
                $userData['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
            }

            if (!empty($userData)) {
                $employee->user->update($userData);
            }

            $employeeData = [];

            foreach ([
                'employee_number',
                'position',
                'department',
                'phone',
                'status',
            ] as $field) {
                if (array_key_exists($field, $validated)) {
                    $employeeData[$field] = $validated[$field];
                }
            }

            if (!empty($employeeData)) {
                $employee->update($employeeData);
            }
        });

        return response()->json([
            'message' => 'Data karyawan berhasil diperbarui.',
            'employee' => $employee->fresh()->load('user'),
        ], 200);
    }

    /**
     * Menghapus data karyawan beserta akun login terkait.
     */
    public function destroy(Employee $employee)
    {
        DB::transaction(function () use ($employee) {
            $user = $employee->user;
            $employee->delete();
            if ($user) {
                $user->delete();
            }
        });

        return response()->json([
            'message' => 'Data karyawan berhasil dihapus.',
        ], 200);
    }
}