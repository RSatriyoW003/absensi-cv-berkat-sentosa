<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\KMeansClusteringService;
use Illuminate\Http\Request;

class KMeansController extends Controller
{
    /**
     * Jalankan analisis K-Means Clustering terhadap kedisiplinan karyawan.
     */
    public function index(Request $request, KMeansClusteringService $kmeansService)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $result = $kmeansService->analyze($startDate, $endDate);

        return response()->json($result, 200);
    }
}
