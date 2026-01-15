<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OccurrenceController;
use App\Http\Controllers\EntryExitController;
use App\Http\Controllers\ExternalVisitController;
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
    Route::get('/citas', function () { return Inertia::render('Placeholder', ['module' => 'Gestión de Citas']); })->name('citas.index');
    Route::get('/licenses', function () { return Inertia::render('Placeholder', ['module' => 'Control de Licencias']); })->name('licenses.index');
    Route::get('/hr', function () { return Inertia::render('Placeholder', ['module' => 'Recursos Humanos']); })->name('hr.index');
});
