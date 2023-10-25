<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/login', [UserController::class, 'showLoginForm'])->name('login');
Route::post('/login', [UserController::class, 'login'])->name('login.submit');

Route::get('/register', [UserController::class, 'showRegisterForm'])->name('register.form');
Route::post('/register', [UserController::class, 'register'])->name('register.submit');

Route::middleware('auth')->group(function () {
    Route::get('/stamp', [AttendanceController::class, 'index'])->name('stamp');
    Route::post('/start_time', [AttendanceController::class, 'startTime'])->name('start_time');
    Route::post('/end_time', [AttendanceController::class, 'endTime'])->name('end_time');
    Route::post('/start_rest', [AttendanceController::class, 'startRest'])->name('start_rest');
    Route::post('/end_rest', [AttendanceController::class, 'endRest'])->name('end_rest');

    Route::get('/attendance', [AttendanceController::class, 'attendance'])->name('attendance');
    Route::get('/attendance/{date}', [AttendanceController::class, 'attendanceDate'])->name('attendance.date');

    Route::post('/logout', [AttendanceController::class, 'logout'])->name('logout');
});
