<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;

Route::get('/', function () {
    return redirect('/login.html');
});

Route::get('/login', function () {
    return redirect('/login.html');
});

Route::get('/dashboard', function () {
    return redirect('/dashboard.html');
});

Route::get('/attendance/history', function () {
    return redirect('/dashboard.html');
});

Route::get('/admin/dashboard', function () {
    return redirect('/admin/dashboard.html');
});

Route::get('/admin/employees', function () {
    return redirect('/admin/employees.html');
});

Route::get('/admin/offices', function () {
    return redirect('/admin/offices.html');
});

Route::get('/admin/attendances', function () {
    return redirect('/admin/attendances.html');
});

Route::get('/admin/schedule', function () {
    return redirect('/admin/schedule.html');
});

Route::get('/admin/analytics', function () {
    return redirect('/admin/analytics.html');
});