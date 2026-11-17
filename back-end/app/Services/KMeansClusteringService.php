<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkSchedule;
use Carbon\Carbon;

class KMeansClusteringService
{
    /**
     * Jalankan analisis K-Means Clustering terhadap kedisiplinan karyawan.
     *
     * @param string|null $startDate Tanggal awal evaluasi (Y-m-d)
     * @param string|null $endDate Tanggal akhir evaluasi (Y-m-d)
     * @return array Hasil clustering, evaluasi Silhouette, WCSS, dan rekomendasi
     */
    public function analyze(?string $startDate = null, ?string $endDate = null): array
    {
        // 1. Ambil jadwal kerja aktif untuk menghitung menit keterlambatan
        $schedule = WorkSchedule::getActiveSchedule();
        $startTime = Carbon::parse($schedule->work_start_time ?? '08:00:00');

        // 2. Query data karyawan aktif
        $employees = Employee::with('user')
            ->where('status', 'active')
            ->get();

        if ($employees->count() < 3) {
            return [
                'status' => 'insufficient_data',
                'message' => 'Data karyawan aktif kurang dari 3 orang. Diperlukan minimal 3 karyawan untuk evaluasi pengelompokan K-Means.',
                'total_employees' => $employees->count(),
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ];
        }

        // 3. Ekstraksi metrik presensi aktual per karyawan
        $metricsData = [];
        $hasAnyAttendance = false;

        foreach ($employees as $employee) {
            $query = Attendance::where('employee_id', $employee->id);

            if ($startDate) {
                $query->whereDate('attendance_date', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('attendance_date', '<=', $endDate);
            }

            $attendances = $query->get();

            if ($attendances->isNotEmpty()) {
                $hasAnyAttendance = true;
            }

            $totalRecords = $attendances->count();
            $presentCount = $attendances->where('status', 'present')->count();
            $lateCount = $attendances->where('status', 'late')->count();
            $leaveCount = $attendances->whereIn('status', ['leave', 'sick'])
                ->where('approval_status', 'approved')
                ->count();
            $absentCount = $attendances->where('status', 'absent')->count();

            // Hitung akumulasi menit keterlambatan nyata
            $lateDurationMinutes = 0;
            foreach ($attendances as $att) {
                if ($att->status === 'late' && $att->check_in) {
                    $checkInTime = Carbon::parse($att->check_in);
                    $scheduledStart = Carbon::parse($att->attendance_date->format('Y-m-d') . ' ' . $startTime->format('H:i:s'));
                    if ($checkInTime->gt($scheduledStart)) {
                        $lateDurationMinutes += abs((int) $checkInTime->diffInMinutes($scheduledStart));
                    }
                }
            }

            // Persentase kehadiran: (Hadir Tepat Waktu + Terlambat) / Total Hari Dicatat
            $attendanceRate = $totalRecords > 0
                ? round((($presentCount + $lateCount) / $totalRecords) * 100, 1)
                : 0.0;

            $metricsData[] = [
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'name' => $employee->user ? $employee->user->name : 'Tanpa Nama',
                'department' => $employee->department ?? 'Umum',
                'position' => $employee->position ?? 'Staff',
                'photo_url' => $employee->photo_url,
                'total_records' => $totalRecords,
                'present_count' => $presentCount,
                'late_count' => $lateCount,
                'late_duration_minutes' => $lateDurationMinutes,
                'leave_count' => $leaveCount,
                'absent_count' => $absentCount,
                'attendance_rate' => $attendanceRate,
                // Vektor fitur mentah untuk K-Means
                'raw_features' => [
                    $attendanceRate,
                    $lateCount,
                    $lateDurationMinutes,
                    $leaveCount,
                    $absentCount,
                ],
            ];
        }

        if (!$hasAnyAttendance) {
            return [
                'status' => 'insufficient_data',
                'message' => 'Belum ada catatan riwayat absensi pada database dalam rentang periode yang dipilih. Analisis K-Means membutuhkan data presensi nyata.',
                'total_employees' => count($metricsData),
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ];
        }

        // 4. Normalisasi Fitur menggunakan Min-Max Normalization [0, 1]
        $featureCount = 5;
        $minValues = array_fill(0, $featureCount, INF);
        $maxValues = array_fill(0, $featureCount, -INF);

        foreach ($metricsData as $item) {
            for ($f = 0; $f < $featureCount; $f++) {
                $val = $item['raw_features'][$f];
                if ($val < $minValues[$f]) $minValues[$f] = $val;
                if ($val > $maxValues[$f]) $maxValues[$f] = $val;
            }
        }

        $normalizedDataset = [];
        foreach ($metricsData as $idx => $item) {
            $norm = [];
            for ($f = 0; $f < $featureCount; $f++) {
                $range = $maxValues[$f] - $minValues[$f];
                $norm[] = $range > 0 ? ($item['raw_features'][$f] - $minValues[$f]) / $range : 0.0;
            }
            $normalizedDataset[$idx] = $norm;
        }

        // 5. Evaluasi Elbow Method & Silhouette Score untuk K = 2, 3, 4
        $maxK = min(count($metricsData) - 1, 3);
        if ($maxK < 2) {
            $maxK = 2;
        }

        $evaluationResults = [];
        $bestK = 3;
        $highestSilhouette = -1.0;

        for ($k = 2; $k <= min($maxK + 1, 4); $k++) {
            if ($k > count($metricsData)) continue;

            $clusterRun = $this->runKMeans($normalizedDataset, $k);
            $silhouette = $this->calculateSilhouetteScore($normalizedDataset, $clusterRun['assignments'], $k);
            
            $evaluationResults[] = [
                'k' => $k,
                'inertia_wcss' => round($clusterRun['inertia'], 4),
                'silhouette_score' => round($silhouette, 4),
            ];

            if ($silhouette > $highestSilhouette) {
                $highestSilhouette = $silhouette;
                $bestK = $k;
            }
        }

        // Gunakan K = 3 sebagai target standar kedisiplinan (Disiplin, Cukup Disiplin, Perlu Evaluasi) jika data mencukupi
        $targetK = (count($metricsData) >= 3) ? 3 : 2;
        $finalClustering = $this->runKMeans($normalizedDataset, $targetK);
        $finalSilhouette = $this->calculateSilhouetteScore($normalizedDataset, $finalClustering['assignments'], $targetK);

        // 6. Analisis Karakteristik Centroid & Pelabelan Objektif
        $clusterGroups = [];
        for ($c = 0; $c < $targetK; $c++) {
            $clusterGroups[$c] = [
                'cluster_index' => $c,
                'members' => [],
                'avg_attendance_rate' => 0,
                'avg_late_count' => 0,
                'avg_late_minutes' => 0,
                'avg_leave_count' => 0,
                'avg_absent_count' => 0,
            ];
        }

        foreach ($finalClustering['assignments'] as $idx => $clusterId) {
            $emp = $metricsData[$idx];
            $clusterGroups[$clusterId]['members'][] = $emp;
        }

        // Hitung rata-rata aktual per klaster
        foreach ($clusterGroups as $c => &$group) {
            $count = count($group['members']);
            if ($count > 0) {
                $group['avg_attendance_rate'] = round(array_sum(array_column($group['members'], 'attendance_rate')) / $count, 1);
                $group['avg_late_count'] = round(array_sum(array_column($group['members'], 'late_count')) / $count, 1);
                $group['avg_late_minutes'] = round(array_sum(array_column($group['members'], 'late_duration_minutes')) / $count, 1);
                $group['avg_leave_count'] = round(array_sum(array_column($group['members'], 'leave_count')) / $count, 1);
                $group['avg_absent_count'] = round(array_sum(array_column($group['members'], 'absent_count')) / $count, 1);
            }
            // Skor kedisiplinan objektif untuk pengurutan label
            $group['discipline_score'] = $group['avg_attendance_rate'] 
                - ($group['avg_late_count'] * 4) 
                - ($group['avg_absent_count'] * 15)
                - ($group['avg_late_minutes'] * 0.05);
        }
        unset($group);

        // Urutkan klaster dari skor kedisiplinan tertinggi ke terendah
        usort($clusterGroups, fn($a, $b) => $b['discipline_score'] <=> $a['discipline_score']);

        // Terapkan label human-readable & rekomendasi manajerial
        $labels = [
            0 => [
                'label' => 'Disiplin',
                'badge_class' => 'badge-success',
                'color' => '#16a34a',
                'description' => 'Tingkat kehadiran konsisten tinggi, jarang atau tidak pernah terlambat, dan tidak memiliki catatan alpa.',
                'recommendation' => 'Pertahankan kedisiplinan kerja. Berikan apresiasi atau reward kinerja berkala.',
            ],
            1 => [
                'label' => 'Cukup Disiplin',
                'badge_class' => 'badge-warning',
                'color' => '#d97706',
                'description' => 'Kehadiran relatif baik, namun terdapat keterlambatan ringan atau beberapa izin resmi.',
                'recommendation' => 'Lakukan monitoring kepatuhan jam kerja agar keterlambatan tidak menjadi kebiasaan.',
            ],
            2 => [
                'label' => 'Perlu Evaluasi',
                'badge_class' => 'badge-danger',
                'color' => '#dc2626',
                'description' => 'Tingkat keterlambatan tinggi, durasi menit telat besar, dan/atau memiliki catatan ketidakhadiran (alpa).',
                'recommendation' => 'Diperlukan evaluasi personal, pemanggilan konseling kedisiplinan oleh HRD, atau surat peringatan.',
            ],
        ];

        $labeledClusters = [];
        $employeeClusterMapping = [];

        foreach ($clusterGroups as $rank => $group) {
            $meta = $labels[$rank] ?? $labels[2];
            $labeledGroup = [
                'cluster_id' => $group['cluster_index'],
                'rank' => $rank + 1,
                'label' => $meta['label'],
                'badge_class' => $meta['badge_class'],
                'color' => $meta['color'],
                'description' => $meta['description'],
                'recommendation' => $meta['recommendation'],
                'member_count' => count($group['members']),
                'metrics_summary' => [
                    'avg_attendance_rate' => $group['avg_attendance_rate'],
                    'avg_late_count' => $group['avg_late_count'],
                    'avg_late_minutes' => $group['avg_late_minutes'],
                    'avg_leave_count' => $group['avg_leave_count'],
                    'avg_absent_count' => $group['avg_absent_count'],
                ],
            ];

            foreach ($group['members'] as $m) {
                $employeeClusterMapping[$m['employee_id']] = [
                    'label' => $meta['label'],
                    'badge_class' => $meta['badge_class'],
                    'color' => $meta['color'],
                    'recommendation' => $meta['recommendation'],
                ];
            }

            $labeledClusters[] = $labeledGroup;
        }

        // Satukan kembali data karyawan dengan label klasternya
        $finalEmployees = [];
        foreach ($metricsData as $emp) {
            $clusterInfo = $employeeClusterMapping[$emp['employee_id']] ?? [
                'label' => 'Belum Terklaster',
                'badge_class' => 'badge-secondary',
                'color' => '#64748b',
                'recommendation' => 'Lakukan evaluasi ulang.',
            ];

            unset($emp['raw_features']);
            $finalEmployees[] = array_merge($emp, [
                'cluster_label' => $clusterInfo['label'],
                'cluster_badge_class' => $clusterInfo['badge_class'],
                'cluster_color' => $clusterInfo['color'],
                'recommendation' => $clusterInfo['recommendation'],
            ]);
        }

        return [
            'status' => 'success',
            'message' => 'Analisis pengelompokan kedisiplinan K-Means berhasil dihitung menggunakan data absensi aktual.',
            'evaluation' => [
                'total_employees_analyzed' => count($finalEmployees),
                'k_used' => $targetK,
                'silhouette_score' => round($finalSilhouette, 4),
                'silhouette_interpretation' => $this->interpretSilhouette($finalSilhouette),
                'inertia_wcss' => round($finalClustering['inertia'], 4),
                'iterations_converged' => $finalClustering['iterations'],
                'elbow_evaluations' => $evaluationResults,
            ],
            'clusters' => $labeledClusters,
            'employees' => $finalEmployees,
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'company' => 'CV Berkat Sentosa',
        ];
    }

    /**
     * Algoritma K-Means komputasi jarak Euclidean dan pergeseran centroid.
     */
    private function runKMeans(array $dataset, int $k, int $maxIterations = 100): array
    {
        $n = count($dataset);
        $d = count($dataset[0]);

        // Inisialisasi Centroid: Pilih secara terdistribusi
        $centroids = [];
        $step = max(1, (int) floor($n / $k));
        for ($i = 0; $i < $k; $i++) {
            $idx = min($i * $step, $n - 1);
            $centroids[$i] = $dataset[$idx];
        }

        $assignments = array_fill(0, $n, 0);
        $iterations = 0;

        for ($iter = 0; $iter < $maxIterations; $iter++) {
            $iterations++;
            $changed = false;

            // 1. Assignment Step: Petakan setiap data point ke centroid terdekat
            for ($i = 0; $i < $n; $i++) {
                $minDist = INF;
                $closestCentroid = 0;

                for ($c = 0; $c < $k; $c++) {
                    $dist = $this->euclideanDistance($dataset[$i], $centroids[$c]);
                    if ($dist < $minDist) {
                        $minDist = $dist;
                        $closestCentroid = $c;
                    }
                }

                if ($assignments[$i] !== $closestCentroid) {
                    $assignments[$i] = $closestCentroid;
                    $changed = true;
                }
            }

            // 2. Update Step: Hitung ulang posisi rata-rata (mean) centroid
            $newCentroids = array_fill(0, $k, array_fill(0, $d, 0.0));
            $counts = array_fill(0, $k, 0);

            for ($i = 0; $i < $n; $i++) {
                $c = $assignments[$i];
                $counts[$c]++;
                for ($f = 0; $f < $d; $f++) {
                    $newCentroids[$c][$f] += $dataset[$i][$f];
                }
            }

            for ($c = 0; $c < $k; $c++) {
                if ($counts[$c] > 0) {
                    for ($f = 0; $f < $d; $f++) {
                        $newCentroids[$c][$f] /= $counts[$c];
                    }
                } else {
                    // Jika klaster kosong, tetapkan kembali ke salah satu data acak
                    $newCentroids[$c] = $dataset[array_rand($dataset)];
                }
            }

            // Cek konvergensi
            $centroidShift = 0.0;
            for ($c = 0; $c < $k; $c++) {
                $centroidShift += $this->euclideanDistance($centroids[$c], $newCentroids[$c]);
            }

            $centroids = $newCentroids;

            if ($centroidShift < 1e-4 && !$changed) {
                break;
            }
        }

        // Hitung total Inertia / WCSS (Within-Cluster Sum of Squares)
        $inertia = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $c = $assignments[$i];
            $inertia += pow($this->euclideanDistance($dataset[$i], $centroids[$c]), 2);
        }

        return [
            'centroids' => $centroids,
            'assignments' => $assignments,
            'inertia' => $inertia,
            'iterations' => $iterations,
        ];
    }

    /**
     * Hitung Silhouette Score untuk mengevaluasi kualitas kohesi dan separasi klaster.
     */
    private function calculateSilhouetteScore(array $dataset, array $assignments, int $k): float
    {
        $n = count($dataset);
        if ($n <= $k || $k < 2) {
            return 0.0;
        }

        $clusterMembers = [];
        for ($i = 0; $i < $n; $i++) {
            $clusterMembers[$assignments[$i]][] = $i;
        }

        $silhouetteSum = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $myCluster = $assignments[$i];
            $myClusterPoints = $clusterMembers[$myCluster];

            // a(i): Rata-rata jarak ke titik lain dalam klaster yang sama (kohesi)
            $a_i = 0.0;
            if (count($myClusterPoints) > 1) {
                $intraDistSum = 0.0;
                foreach ($myClusterPoints as $otherIdx) {
                    if ($i !== $otherIdx) {
                        $intraDistSum += $this->euclideanDistance($dataset[$i], $dataset[$otherIdx]);
                    }
                }
                $a_i = $intraDistSum / (count($myClusterPoints) - 1);
            }

            // b(i): Jarak rata-rata terdekat ke klaster tetangga terdekat (separasi)
            $b_i = INF;
            foreach ($clusterMembers as $otherCluster => $otherPoints) {
                if ($otherCluster === $myCluster || empty($otherPoints)) {
                    continue;
                }
                $interDistSum = 0.0;
                foreach ($otherPoints as $otherIdx) {
                    $interDistSum += $this->euclideanDistance($dataset[$i], $dataset[$otherIdx]);
                }
                $avgDist = $interDistSum / count($otherPoints);
                if ($avgDist < $b_i) {
                    $b_i = $avgDist;
                }
            }

            if ($b_i === INF) {
                $b_i = 0.0;
            }

            $maxVal = max($a_i, $b_i);
            $s_i = ($maxVal > 0) ? ($b_i - $a_i) / $maxVal : 0.0;
            $silhouetteSum += $s_i;
        }

        return $silhouetteSum / $n;
    }

    /**
     * Jarak Euclidean antara dua vektor.
     */
    private function euclideanDistance(array $v1, array $v2): float
    {
        $sum = 0.0;
        $d = count($v1);
        for ($i = 0; $i < $d; $i++) {
            $diff = $v1[$i] - $v2[$i];
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    /**
     * Interpretasi naratif nilai Silhouette Score.
     */
    private function interpretSilhouette(float $score): string
    {
        if ($score >= 0.70) {
            return 'Struktur klaster sangat kuat (Strong Clustering Structure).';
        } elseif ($score >= 0.50) {
            return 'Struktur klaster wajar dan representatif (Reasonable Clustering Structure).';
        } elseif ($score >= 0.25) {
            return 'Struktur klaster lemah namun bermakna (Weak Clustering Structure).';
        } else {
            return 'Klaster tumpang-tindih atau data relatif homogen.';
        }
    }
}
