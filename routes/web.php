<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware('auth')->get('/reports/activity-summary', [ReportController::class, 'activitySummary'])
    ->name('reports.activity-summary');

Route::middleware('auth')->get('/reports/class-attendance', [ReportController::class, 'classAttendance'])
    ->name('reports.class-attendance');

Route::middleware('auth')->get('/reports/daily-school-activities', [ReportController::class, 'dailySchoolActivities'])
    ->name('reports.daily-school-activities');

Route::middleware('auth')->get('/reports/daily-home-activities', [ReportController::class, 'dailyHomeActivities'])
    ->name('reports.daily-home-activities');
