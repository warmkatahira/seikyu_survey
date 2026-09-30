<?php

use App\Http\Controllers\Admin\ChoiceCategoryController;
use App\Http\Controllers\Admin\ChoiceOptionController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\MasterCsvController;
use App\Http\Controllers\Admin\OfficeController;
use App\Http\Controllers\Admin\SurveyResponseExportController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SurveyResponseController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::redirect('/', '/responses');
    Route::get('/responses/excel', [SurveyResponseController::class, 'export'])->name('responses.export');
    Route::resource('responses', SurveyResponseController::class)
        ->parameters(['responses' => 'response']);

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/responses/csv', SurveyResponseExportController::class)->name('responses.export');

        Route::resource('offices', OfficeController::class)->except('show');
        Route::resource('employees', EmployeeController::class)->except('show');
        Route::resource('customers', CustomerController::class)->except('show');

        Route::get('/masters/{master}/csv', [MasterCsvController::class, 'show'])->name('masters.export');
        Route::post('/masters/{master}/csv', [MasterCsvController::class, 'store'])->name('masters.import');

        Route::get('/choices', [ChoiceCategoryController::class, 'index'])->name('choices.index');
        Route::post('/choices/{category}/options', [ChoiceOptionController::class, 'store'])->name('choices.options.store');
        Route::put('/choice-options/{option}', [ChoiceOptionController::class, 'update'])->name('choices.options.update');
        Route::delete('/choice-options/{option}', [ChoiceOptionController::class, 'destroy'])->name('choices.options.destroy');
    });
});
