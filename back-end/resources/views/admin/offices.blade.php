<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola Lokasi Kantor - CV Berkat Sentosa</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .header {
            background: #1f2937;
            color: white;
            padding: 25px;
        }

        .header h1 {
            margin: 0 0 8px;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .office {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .office h3 {
            margin-top: 0;
        }

        .status {
            font-weight: bold;
        }

        .active {
            color: green;
        }

        .inactive {
            color: red;
        }

        .button {
            display: inline-block;
            padding: 10px 15px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-right: 10px;
        }

        .back {
            background: #6b7280;
        }

        .error {
            color: red;
        }

        input,
        select {
            box-sizing: border-box;
        }
    </style>
</head>

<body>

<div class="header">
    <h1>Kelola Lokasi Kantor</h1>
    <p>Sistem Informasi Absensi Karyawan CV Berkat Sentosa</p>
</div>

<div class="container">

    <div class="card">

        <h2>Daftar Lokasi Kantor</h2>

        <button
            type="button"
            class="button"
            onclick="showAddForm()"
        >
            + Tambah Lokasi Kantor
        </button>

        <div id="add-form" style="display:none; margin-top:20px;">

            <h3>Tambah Lokasi Kantor</h3>

            <form onsubmit="addOffice(event)">

                <p>
                    <label>Nama Kantor</label><br>
                    <input
                        type="text"
                        id="name"
                        required
                        style="width:100%; padding:10px;"
                    >
                </p>

                <p>
                    <label>Latitude</label><br>
                    <input
                        type="number"
                        id="latitude"
                        step="any"
                        required
                        style="width:100%; padding:10px;"
                    >
                </p>

                <p>
                    <label>Longitude</label><br>
                    <input
                        type="number"
                        id="longitude"
                        step="any"
                        required
                        style="width:100%; padding:10px;"
                    >
                </p>

                <p>
                    <label>Radius (meter)</label><br>
                    <input
                        type="number"
                        id="radius"
                        min="1"
                        required
                        style="width:100%; padding:10px;"
                    >
                </p>

                <p>
                    <label>Status</label><br>
                    <select
                        id="status"
                        style="width:100%; padding:10px;"
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </p>

                <button type="submit" class="button">
                    Simpan
                </button>

                <button
                    type="button"
                    class="button back"
                    onclick="hideAddForm()"
                >
                    Batal
                </button>

            </form>

        </div>

        <!-- Daftar kantor ditampilkan di sini -->
        <div id="office-list" style="margin-top:20px;">
            Memuat data kantor...
        </div>

    </div>

    <div class="card">

        <a href="/admin/dashboard" class="button back">
            Kembali ke Dashboard Admin
        </a>

        <form action="/logout" method="POST" style="display:inline;">
            @csrf

            <button
                type="submit"
                class="button"
                style="border:none; cursor:pointer;"
            >
                Logout
            </button>
        </form>

    </div>

</div>

<script>

    const apiToken = @json(session('api_token'));

    async function loadOffices() {

        try {

            const response = await fetch('/api/admin/offices', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${apiToken}`,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(
                    data.message || 'Gagal mengambil data kantor.'
                );
            }

            const officeList =
                document.getElementById('office-list');

            if (!data.offices || data.offices.length === 0) {

                officeList.innerHTML = `
                    <p>Belum ada lokasi kantor.</p>
                `;

                return;
            }

            officeList.innerHTML = data.offices.map(office => `

                <div class="office">

                    <h3>${office.name}</h3>

                    <p>
                        <strong>Latitude:</strong>
                        ${office.latitude}
                    </p>

                    <p>
                        <strong>Longitude:</strong>
                        ${office.longitude}
                    </p>

                    <p>
                        <strong>Radius:</strong>
                        ${office.radius} meter
                    </p>

                    <p>
                        <strong>Status:</strong>

                        <span class="status ${
                            office.status === 'active'
                                ? 'active'
                                : 'inactive'
                        }">
                            ${office.status}
                        </span>

                    </p>

                </div>

            `).join('');

        } catch (error) {

            console.error(error);

            document.getElementById('office-list').innerHTML = `
                <p class="error">
                    ${error.message}
                </p>
            `;
        }
    }


    function showAddForm() {

        document.getElementById('add-form').style.display = 'block';

    }


    function hideAddForm() {

        document.getElementById('add-form').style.display = 'none';

    }


    async function addOffice(event) {

        event.preventDefault();

        const data = {

            name: document.getElementById('name').value,

            latitude:
                document.getElementById('latitude').value,

            longitude:
                document.getElementById('longitude').value,

            radius:
                document.getElementById('radius').value,

            status:
                document.getElementById('status').value

        };


        try {

            const response = await fetch('/api/admin/offices', {

                method: 'POST',

                headers: {

                    'Authorization': `Bearer ${apiToken}`,

                    'Accept': 'application/json',

                    'Content-Type': 'application/json'

                },

                body: JSON.stringify(data)

            });


            const result = await response.json();


            if (!response.ok) {

                throw new Error(
                    result.message ||
                    'Gagal menambahkan lokasi kantor.'
                );

            }


            alert('Lokasi kantor berhasil ditambahkan.');


            document.querySelector('#add-form form').reset();


            hideAddForm();


            await loadOffices();


        } catch (error) {

            console.error(error);

            alert(error.message);

        }

    }


    loadOffices();

</script>

</body>
</html>