<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "API Sistem Absensi CV Berkat Sentosa",
    version: "1.0.0",
    description: "Dokumentasi API untuk sistem absensi karyawan CV Berkat Sentosa."
)]

#[OA\Server(
    url: "http://127.0.0.1:8000",
    description: "Local Development Server"
)]

#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "http",
    scheme: "bearer",
    bearerFormat: "Token"
)]

class OpenApi
{
}
