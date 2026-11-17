<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - Absensi CV Berkat Sentosa</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
        }

        .header {
            background: white;
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .header h1 {
            font-size: 22px;
        }

        .header p {
            color: #6b7280;
            margin-top: 5px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 24px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .status {
            font-size: 24px;
            font-weight: bold;
            margin-top: 10px;
        }

        .info {
            margin-top: 10px;
            color: #4b5563;
        }

        .button {
            display: block;
            width: 100%;
            padding: 13px;
            margin-top: 12px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }

        .logout {
            background: #dc2626;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>Dashboard Absensi</h1>
        <p>Sistem Informasi Absensi Karyawan CV Berkat Sentosa</p>
    </div>

    <div class="container">

        <div class="card">
            <h2>Selamat Datang</h2>
            <div id="employee-name" class="status">
                Memuat...
            </div>

            <div id="employee-info" class="info">
                Memuat data employee...
            </div>
        </div>

        <div class="card">
            <h2>Status Hari Ini</h2>

            <div id="today-status" class="status">
                Memuat...
            </div>

            <div id="attendance-info" class="info">
                Memuat data absensi...
            </div>

            <button
                id="attendance-button"
                class="button"
                onclick="handleAttendance()"
            >
                Memuat...
            </button>
        </div>

        <div class="card">
            <h2>Absensi Minggu Ini</h2>

            <div id="weekly-info" class="status">
                Memuat...
            </div>
        </div>

        <a href="/attendance/history" class="button">
    Riwayat Absensi
</a>

<form action="/logout" method="POST">
    @csrf

    <button type="submit" class="button logout">
        Logout
    </button>
</form>

    </div>

    <script>
        const apiToken = @json(session('api_token'));

        async function loadDashboard() {
            try {
                const response = await fetch('/api/dashboard', {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${apiToken}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Gagal mengambil dashboard.');
                }

                document.getElementById('employee-name').textContent =
                    data.employee.name;

                document.getElementById('employee-info').textContent =
                    `${data.employee.employee_number} • ${data.employee.position} • ${data.employee.department}`;

                let statusText = 'Belum bekerja';

                if (data.today.status === 'working') {
                    statusText = 'Sedang bekerja';
                } else if (data.today.status === 'completed') {
                    statusText = 'Sudah selesai';
                }

                document.getElementById('today-status').textContent =
                    statusText;

                let attendanceText = '';

                if (data.today.check_in) {
                    attendanceText +=
                        `Check-in: ${formatDate(data.today.check_in)}<br>`;
                }

                if (data.today.check_out) {
                    attendanceText +=
                        `Check-out: ${formatDate(data.today.check_out)}<br>`;
                }

                if (data.today.office) {
                    attendanceText +=
                        `Kantor: ${data.today.office.name}`;
                }

                document.getElementById('attendance-info').innerHTML =
                    attendanceText || 'Belum ada absensi hari ini.';

                const button = document.getElementById('attendance-button');

                if (data.today.status === 'not_working') {
                    button.textContent = 'Mulai Kerja';
                } else if (data.today.status === 'working') {
                    button.textContent = 'Selesai Kerja';
                } else {
                    button.textContent = 'Absensi Selesai';
                    button.disabled = true;
                }

                document.getElementById('weekly-info').textContent =
                    `${data.weekly.total_attendance} kali absensi`;

            } catch (error) {
                console.error(error);

                document.getElementById('today-status').textContent =
                    'Gagal memuat data';

                document.getElementById('attendance-info').textContent =
                    error.message;
            }
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleString('id-ID');
        }

    async function handleAttendance() {
    if (!navigator.geolocation) {
        alert('Browser tidak mendukung fitur GPS.');
        return;
    }

    const button = document.getElementById('attendance-button');

    // Tentukan jenis absensi sebelum teks tombol berubah
    const isCheckOut = button.textContent.trim() === 'Selesai Kerja';

    const endpoint = isCheckOut
        ? '/api/attendance/check-out'
        : '/api/attendance/check-in';

    button.disabled = true;
    button.textContent = 'Mengambil lokasi...';

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;

            console.log('Latitude:', latitude);
            console.log('Longitude:', longitude);
            console.log('Endpoint:', endpoint);

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${apiToken}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        latitude: latitude,
                        longitude: longitude
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message || 'Absensi gagal.'
                    );
                }

                alert(data.message);

                await loadDashboard();

            } catch (error) {
                console.error(error);
                alert(error.message);
            }

            button.disabled = false;
        },

        (error) => {
            console.error(error);

            button.disabled = false;

            if (error.code === 1) {
                alert(
                    'Akses lokasi ditolak. Silakan izinkan lokasi pada browser.'
                );
            } else if (error.code === 2) {
                alert('Lokasi tidak dapat ditemukan.');
            } else if (error.code === 3) {
                alert('Pengambilan lokasi terlalu lama.');
            } else {
                alert('Terjadi kesalahan saat mengambil lokasi.');
            }
        }
    );
}
        loadDashboard();
    </script>

</body>
</html>