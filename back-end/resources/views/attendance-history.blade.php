<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Riwayat Absensi - CV Berkat Sentosa</title>

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

        .attendance-item {
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 0;
        }

        .attendance-item:last-child {
            border-bottom: none;
        }

        .date {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .info {
            color: #4b5563;
            line-height: 1.8;
        }

        .status {
            display: inline-block;
            margin-top: 8px;
            padding: 5px 10px;
            border-radius: 6px;
            background: #dcfce7;
            color: #166534;
            font-weight: bold;
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
        <h1>Riwayat Absensi</h1>
        <p>Sistem Informasi Absensi Karyawan CV Berkat Sentosa</p>
    </div>

    <div class="container">

        <div class="card">
            <h2>Riwayat Absensi</h2>

            <div id="attendance-list">
                Memuat riwayat absensi...
            </div>
        </div>

        <a href="/dashboard" class="button">
            Kembali ke Dashboard
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

        async function loadHistory() {
            try {
                const response = await fetch('/api/attendance/history', {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${apiToken}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message || 'Gagal mengambil riwayat absensi.'
                    );
                }

                const list = document.getElementById('attendance-list');

                if (!data.attendance || data.attendance.length === 0) {
                    list.innerHTML = 'Belum ada riwayat absensi.';
                    return;
                }

                list.innerHTML = '';

                data.attendance.forEach((attendance) => {
                    const item = document.createElement('div');

                    item.className = 'attendance-item';

                    const date = new Date(
                        attendance.attendance_date
                    ).toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric'
                    });

                    const checkIn = attendance.check_in
                        ? formatDate(attendance.check_in)
                        : '-';

                    const checkOut = attendance.check_out
                        ? formatDate(attendance.check_out)
                        : '-';

                    const office = attendance.office_location
                        ? attendance.office_location.name
                        : 'Tidak diketahui';

                    item.innerHTML = `
                        <div class="date">${date}</div>

                        <div class="info">
                            Kantor: ${office}<br>
                            Check-in: ${checkIn}<br>
                            Check-out: ${checkOut}
                        </div>

                        <div class="status">
                            ${attendance.status}
                        </div>
                    `;

                    list.appendChild(item);
                });

            } catch (error) {
                console.error(error);

                document.getElementById('attendance-list').textContent =
                    error.message;
            }
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleString('id-ID');
        }

        loadHistory();
    </script>

</body>
</html>