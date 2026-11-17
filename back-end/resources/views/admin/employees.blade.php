<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola Karyawan - CV Berkat Sentosa</title>

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
            max-width: 1000px;
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

        .employee-item {
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 0;
        }

        .employee-item:last-child {
            border-bottom: none;
        }

        .employee-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .info {
            color: #4b5563;
            line-height: 1.7;
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

        .secondary {
            background: #6b7280;
        }

        .logout {
            background: #dc2626;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>Kelola Karyawan</h1>
        <p>Sistem Informasi Absensi Karyawan CV Berkat Sentosa</p>
    </div>

    <div class="container">

        <div class="card">
            <h2>Daftar Karyawan</h2>

            <div id="employee-list">
                Memuat data karyawan...
            </div>
        </div>

        <a href="/admin/dashboard" class="button secondary">
            Kembali ke Dashboard Admin
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

        async function loadEmployees() {
            try {
                const response = await fetch('/api/admin/employees', {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${apiToken}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message || 'Gagal mengambil data karyawan.'
                    );
                }

                const list = document.getElementById('employee-list');

                if (!data.employees || data.employees.length === 0) {
                    list.textContent = 'Belum ada data karyawan.';
                    return;
                }

                list.innerHTML = '';

                data.employees.forEach((employee) => {

                    const item = document.createElement('div');

                    item.className = 'employee-item';

                    const name = employee.user
                        ? employee.user.name
                        : 'Tidak diketahui';

                    const email = employee.user
                        ? employee.user.email
                        : '-';

                    item.innerHTML = `
                        <div class="employee-name">
                            ${name}
                        </div>

                        <div class="info">
                            Nomor Employee: ${employee.employee_number}<br>
                            Email: ${email}<br>
                            Jabatan: ${employee.position}<br>
                            Departemen: ${employee.department || '-'}<br>
                            Telepon: ${employee.phone || '-'}
                        </div>

                        <div class="status">
                            ${employee.status}
                        </div>
                    `;

                    list.appendChild(item);
                });

            } catch (error) {
                console.error(error);

                document.getElementById('employee-list').textContent =
                    error.message;
            }
        }

        loadEmployees();
    </script>

</body>
</html>