<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ChannelController;
use App\Http\Controllers\Admin\FactController;
use App\Http\Controllers\Admin\PollScheduleExceptionController;
use App\Http\Controllers\Admin\ReminderAbsencePeriodController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.channels');
        })->name('home');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('channels', [ChannelController::class, 'index'])->name('channels');
        Route::post('channels', [ChannelController::class, 'update'])->name('channels.update');

        Route::get('facts', [FactController::class, 'index'])->name('facts');
        Route::post('facts', [FactController::class, 'store'])->name('facts.store');
        Route::post('facts/{fact}/toggle', [FactController::class, 'toggle'])->name('facts.toggle');
        Route::delete('facts/{fact}', [FactController::class, 'destroy'])->name('facts.destroy');

        Route::get('schedule-exceptions', [PollScheduleExceptionController::class, 'index'])->name('schedule-exceptions');
        Route::post('schedule-exceptions', [PollScheduleExceptionController::class, 'store'])->name('schedule-exceptions.store');
        Route::delete('schedule-exceptions/{pollScheduleException}', [PollScheduleExceptionController::class, 'destroy'])->name('schedule-exceptions.destroy');

        Route::get('absence-periods', [ReminderAbsencePeriodController::class, 'index'])->name('absence-periods');
        Route::post('absence-periods', [ReminderAbsencePeriodController::class, 'store'])->name('absence-periods.store');
        Route::delete('absence-periods/{reminderAbsencePeriod}', [ReminderAbsencePeriodController::class, 'destroy'])->name('absence-periods.destroy');
    });
});
