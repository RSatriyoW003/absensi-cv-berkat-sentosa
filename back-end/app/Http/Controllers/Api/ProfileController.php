<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();

        $employee = $user->employee;

        return response()->json([
            'message' => 'Profil berhasil diambil.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'employee' => $employee ? [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'position' => $employee->position,
                'department' => $employee->department,
                'phone' => $employee->phone,
                'status' => $employee->status,
                'photo' => $employee->photo,
                'photo_url' => $employee->photo_url,
            ] : null,
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'message' => 'Data employee tidak ditemukan.',
            ], 404);
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'photo_base64' => 'nullable|string',
        ]);

        if ($request->filled('name')) {
            $user->name = $request->name;
            $user->save();
        }

        if ($request->has('phone')) {
            $employee->phone = $request->phone;
        }

        // Upload file foto via multipart/form-data
        if ($request->hasFile('photo')) {
            if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
                Storage::disk('public')->delete($employee->photo);
            }

            $path = $request->file('photo')->store('photos', 'public');
            $employee->photo = $path;
        }
        // Upload foto via base64 data URL
        elseif ($request->filled('photo_base64')) {
            $base64 = $request->photo_base64;
            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
                $type = strtolower($type[1]);

                $image = base64_decode($base64);
                if ($image !== false) {
                    if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
                        Storage::disk('public')->delete($employee->photo);
                    }

                    $fileName = 'photos/' . Str::uuid() . '.' . ($type === 'jpeg' ? 'jpg' : $type);
                    Storage::disk('public')->put($fileName, $image);
                    $employee->photo = $fileName;
                }
            }
        }

        $employee->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'employee' => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'position' => $employee->position,
                'department' => $employee->department,
                'phone' => $employee->phone,
                'status' => $employee->status,
                'photo' => $employee->photo,
                'photo_url' => $employee->photo_url,
            ],
        ], 200);
    }
}