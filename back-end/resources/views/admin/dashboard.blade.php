<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - CV Berkat Sentosa</title>

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
            max-width: 900px;
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

        .card h2 {
            margin-bottom: 10px;
        }

        .menu {
            display: grid;
            gap: 12px;
        }

        .button {
            display: block;
            width: 100%;
            padding: 14px;
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
        <h1>Admin Dashboard</h1>
        <p>Sistem Informasi Absensi Karyawan CV Berkat Sentosa</p>
    </div>

    <div class="container">

        <div class="card">
            <h2>Selamat Datang, Admin</h2>

            <p>
                Gunakan menu berikut untuk mengelola sistem absensi
                karyawan.
            </p>
        </div>

        <div class="card">
            <h2>Menu Admin</h2>

            <div class="menu">

                <a href="/admin/employees" class="button">
                    Kelola Karyawan
                </a>

                <a href="/admin/offices" class="button">
                    Kelola Lokasi Kantor
                </a>

                <a href="/admin/attendances" class="button">
                    Data Absensi
                </a>

            </div>
        </div>

        <form action="/logout" method="POST">
            @csrf

            <button type="submit" class="button logout">
                Logout
            </button>
        </form>

    </div>

</body>
</html>