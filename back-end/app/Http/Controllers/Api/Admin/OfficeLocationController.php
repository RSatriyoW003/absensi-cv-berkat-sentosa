<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\OfficeLocation;
use Illuminate\Http\Request;

class OfficeLocationController extends Controller
{
    /**
     * Mengambil data lokasi kantor CV Berkat Sentosa.
     */
    public function index(Request $request)
    {
        $office = OfficeLocation::where('status', 'active')->first() ?: OfficeLocation::first();

        if (!$office) {
            $office = OfficeLocation::create([
                'name' => 'Kantor CV Berkat Sentosa (Pusat)',
                'latitude' => -7.2575000,
                'longitude' => 112.7521000,
                'radius' => 100,
                'status' => 'active',
            ]);
        }

        return response()->json([
            'message' => 'Lokasi kantor CV Berkat Sentosa berhasil diambil.',
            'office' => $office,
            'offices' => [$office], // Backward compatibility
        ], 200);
    }

    /**
     * Memperbarui lokasi & radius kantor tunggal CV Berkat Sentosa.
     */
    public function update(Request $request, ?OfficeLocation $office = null)
    {
        if (!$office || !$office->exists) {
            $office = OfficeLocation::where('status', 'active')->first() ?: OfficeLocation::first();
        }

        // Sanitasi koordinat jika ada spasi, koma desimal, atau format string
        $data = $request->all();
        if (isset($data['latitude'])) {
            $latRaw = is_string($data['latitude']) ? str_replace(',', '.', trim($data['latitude'])) : $data['latitude'];
            $data['latitude'] = is_numeric($latRaw) ? (float) $latRaw : $latRaw;
        }
        if (isset($data['longitude'])) {
            $lngRaw = is_string($data['longitude']) ? str_replace(',', '.', trim($data['longitude'])) : $data['longitude'];
            $data['longitude'] = is_numeric($lngRaw) ? (float) $lngRaw : $lngRaw;
        }
        $request->merge($data);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:1|max:500000',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        if (!$office) {
            $office = OfficeLocation::create([
                'name' => $validated['name'] ?? 'Kantor CV Berkat Sentosa (Pusat)',
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'radius' => $validated['radius'],
                'status' => $validated['status'] ?? 'active',
            ]);
        } else {
            $office->update([
                'name' => $validated['name'] ?? $office->name ?? 'Kantor CV Berkat Sentosa (Pusat)',
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'radius' => $validated['radius'],
                'status' => $validated['status'] ?? $office->status ?? 'active',
            ]);
        }

        return response()->json([
            'message' => 'Lokasi dan radius kantor CV Berkat Sentosa berhasil disimpan ke database.',
            'office' => $office->fresh(),
            'offices' => [$office->fresh()],
        ], 200);
    }

    public function show(OfficeLocation $office)
    {
        return response()->json([
            'message' => 'Detail lokasi kantor berhasil diambil.',
            'office' => $office,
        ], 200);
    }

    public function store(Request $request)
    {
        return $this->update($request, null);
    }

    public function destroy(OfficeLocation $office)
    {
        return response()->json([
            'message' => 'Sistem menggunakan lokasi tunggal CV Berkat Sentosa sehingga kantor utama tidak dapat dihapus.',
        ], 422);
    }
}