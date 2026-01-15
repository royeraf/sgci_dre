<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OccurrenceController;
use App\Http\Controllers\EntryExitController;
use App\Http\Controllers\ExternalVisitController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\HRController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Public Appointments Portal
Route::get('/citas/portal', [AppointmentController::class, 'create'])->name('citas.portal');
Route::post('/citas/request', [AppointmentController::class, 'store'])->name('citas.request');
Route::get('/citas/status/{dni}', [AppointmentController::class, 'checkStatus'])->name('citas.check-status');

// Protected routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    Route::get('/', function () {
        return redirect('/dashboard');
    });
    
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
    // Occurrences routes
    Route::get('/occurrences', [OccurrenceController::class, 'index'])->name('occurrences.index');
    Route::post('/occurrences', [OccurrenceController::class, 'store'])->name('occurrences.store');
    Route::get('/occurrences/weekly-report', [OccurrenceController::class, 'weeklyReport'])->name('occurrences.weekly-report');
    Route::get('/occurrences/{occurrence}', [OccurrenceController::class, 'show'])->name('occurrences.show');
    Route::get('/api/occurrences/summary', [OccurrenceController::class, 'summary'])->name('occurrences.summary');
    
    // Entry/Exit routes
    Route::prefix('entry-exits')->name('entry-exits.')->group(function () {
        Route::get('/', [EntryExitController::class, 'index'])->name('index');
        Route::post('/', [EntryExitController::class, 'store'])->name('store');
        Route::patch('/{entryExit}/return', [EntryExitController::class, 'registerReturn'])->name('return');
        Route::get('/{entryExit}/pdf', [EntryExitController::class, 'generatePdf'])->name('pdf');
        Route::get('/api/pending', [EntryExitController::class, 'pendingReturns'])->name('pending');
    });

    // Placeholder routes for migrated modules
    // Visitors routes
    Route::prefix('visitors')->name('visitors.')->group(function () {
        Route::get('/', [ExternalVisitController::class, 'index'])->name('index');
        Route::post('/', [ExternalVisitController::class, 'store'])->name('store');
        Route::patch('/{visit}/exit', [ExternalVisitController::class, 'registerExit'])->name('exit');
        Route::get('/{visit}/ticket', [ExternalVisitController::class, 'generateTicket'])->name('ticket');
    });
    Route::get('/vehicles', function () { return Inertia::render('Placeholder', ['module' => 'Control Vehicular']); })->name('vehicles.index');
    
    // Gestión de Citas
    Route::prefix('citas')->name('citas.')->group(function () {
        Route::get('/', [AppointmentController::class, 'adminIndex'])->name('index');
        Route::get('/list', [AppointmentController::class, 'index'])->name('list');
        Route::put('/{id}/status', [AppointmentController::class, 'updateStatus'])->name('update-status');
    });

    Route::get('/licenses', function () { return Inertia::render('Placeholder', ['module' => 'Control de Licencias']); })->name('licenses.index');
    
    // Recursos Humanos
    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/', [HRController::class, 'index'])->name('index');
        
        // Employees
        Route::get('/employees', [HRController::class, 'getEmployees'])->name('employees.list');
        Route::post('/employees', [HRController::class, 'storeEmployee'])->name('employees.store');
        Route::get('/employees/{id}', [HRController::class, 'getEmployee'])->name('employees.show');
        Route::put('/employees/{id}', [HRController::class, 'updateEmployee'])->name('employees.update');
        Route::get('/employee/dni/{dni}', [HRController::class, 'getEmployeeByDni'])->name('employees.by-dni');
        
        // Vacations
        Route::get('/vacations', [HRController::class, 'getVacations'])->name('vacations.list');
        Route::post('/vacations', [HRController::class, 'storeVacation'])->name('vacations.store');
        
        // Summary
        Route::get('/summary', [HRController::class, 'getSummary'])->name('summary');
    });
});
