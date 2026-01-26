<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OccurrenceController;
use App\Http\Controllers\EntryExitController;
use App\Http\Controllers\ExternalVisitController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\HRController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Root redirect (public)
Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

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

    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Profile routes
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    
    // Occurrences routes
    Route::middleware('role:ROL006,ROL007')->group(function () {
        Route::get('/occurrences', [OccurrenceController::class, 'index'])->name('occurrences.index');
        Route::post('/occurrences', [OccurrenceController::class, 'store'])->name('occurrences.store');
        Route::get('/occurrences/weekly-report', [OccurrenceController::class, 'weeklyReport'])->name('occurrences.weekly-report');
        Route::get('/occurrences/{occurrence}', [OccurrenceController::class, 'show'])->name('occurrences.show');
        Route::get('/api/occurrences/summary', [OccurrenceController::class, 'summary'])->name('occurrences.summary');
    });
    
    // Entry/Exit routes
    Route::middleware('role:ROL006,ROL007')->prefix('entry-exits')->name('entry-exits.')->group(function () {
        Route::get('/', [EntryExitController::class, 'index'])->name('index');
        Route::post('/', [EntryExitController::class, 'store'])->name('store');
        // API routes must come BEFORE dynamic parameter routes
        Route::get('/api/pending', [EntryExitController::class, 'pendingReturns'])->name('pending');
        Route::get('/api/absent', [EntryExitController::class, 'getAbsentPersonnel'])->name('absent');
        Route::get('/api/search-personnel', [EntryExitController::class, 'searchPersonnel'])->name('search-personnel');
        // Dynamic parameter routes after static routes
        Route::patch('/{entryExit}/return', [EntryExitController::class, 'registerReturn'])->name('return');
        Route::get('/{entryExit}/pdf', [EntryExitController::class, 'generatePdf'])->name('pdf');
    });

    // Visitors routes
    Route::middleware('role:ROL006,ROL007')->prefix('visitors')->name('visitors.')->group(function () {
        Route::get('/', [ExternalVisitController::class, 'index'])->name('index');
        Route::post('/', [ExternalVisitController::class, 'store'])->name('store');
        Route::get('/api/consultar-dni', [ExternalVisitController::class, 'consultarDni'])->name('consultar-dni');
        Route::get('/api/report-stats', [ExternalVisitController::class, 'reportStats'])->name('report-stats');
        Route::get('/report/pdf', [ExternalVisitController::class, 'generateReportPdf'])->name('report-pdf');
        Route::patch('/{visit}/exit', [ExternalVisitController::class, 'registerExit'])->name('exit');
        Route::get('/{visit}/ticket', [ExternalVisitController::class, 'generateTicket'])->name('ticket');
    });

    // Control Vehicular
    Route::middleware('role:ROL006,ROL007')->prefix('vehicles')->name('vehicles.')->group(function () {
        Route::get('/', [VehicleController::class, 'index'])->name('index');
        Route::get('/summary', [VehicleController::class, 'getSummary'])->name('summary');
        
        // Vehicles (Inventory)
        Route::get('/inventory', [VehicleController::class, 'getVehicles'])->name('inventory.list');
        Route::post('/inventory', [VehicleController::class, 'storeVehicle'])->name('inventory.store');
        Route::put('/inventory/{id}', [VehicleController::class, 'updateVehicle'])->name('inventory.update');
        Route::delete('/inventory/{id}', [VehicleController::class, 'deleteVehicle'])->name('inventory.delete');
        
        // Commissions
        Route::get('/commissions', [VehicleController::class, 'getCommissions'])->name('commissions.list');
        Route::post('/commissions', [VehicleController::class, 'storeCommission'])->name('commissions.store');
        Route::put('/commissions/{id}', [VehicleController::class, 'updateCommission'])->name('commissions.update');
        
        // Maintenance
        Route::get('/maintenance', [VehicleController::class, 'getMaintenances'])->name('maintenance.list');
        Route::post('/maintenance', [VehicleController::class, 'storeMaintenance'])->name('maintenance.store');
        
        // Handovers
        Route::get('/handovers', [VehicleController::class, 'getHandovers'])->name('handovers.list');
        Route::post('/handovers', [VehicleController::class, 'storeHandover'])->name('handovers.store');
        
        // Service Requirements
        Route::get('/service-requirements', [VehicleController::class, 'getServiceRequirements'])->name('service-requirements.list');
        Route::post('/service-requirements', [VehicleController::class, 'storeServiceRequirement'])->name('service-requirements.store');
    });
    
    // Gestión de Citas
    Route::middleware('role:ROL010')->prefix('citas')->name('citas.')->group(function () {
        Route::get('/', [AppointmentController::class, 'adminIndex'])->name('index');
        Route::get('/list', [AppointmentController::class, 'index'])->name('list');
        Route::put('/{id}/status', [AppointmentController::class, 'updateStatus'])->name('update-status');
    });

    // Bienestar Social / Licencias
    Route::middleware('role:ROL008')->prefix('bienestar')->name('bienestar.')->group(function () {
        Route::get('/', [LicenseController::class, 'index'])->name('index');
        Route::get('/api/licenses', [LicenseController::class, 'getLicenses'])->name('licenses.list');
        Route::get('/api/employees', [LicenseController::class, 'getEmployeesBalance'])->name('employees.list');
        Route::get('/api/summary', [LicenseController::class, 'getSummary'])->name('summary');
        Route::get('/api/employee/{dni}', [LicenseController::class, 'getEmployeeByDni'])->name('employee.by-dni');
        Route::post('/api/register', [LicenseController::class, 'store'])->name('register');
    });
    
    // Recursos Humanos
    Route::middleware('role:ROL009')->prefix('hr')->name('hr.')->group(function () {
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
        Route::put('/vacations/{id}', [HRController::class, 'updateVacation'])->name('vacations.update');
        Route::delete('/vacations/{id}', [HRController::class, 'deleteVacation'])->name('vacations.delete');

        // Areas
        Route::get('/areas', [HRController::class, 'getAreas'])->name('areas.list');
        Route::post('/areas', [HRController::class, 'storeArea'])->name('areas.store');
        Route::put('/areas/{id}', [HRController::class, 'updateArea'])->name('areas.update');
        Route::delete('/areas/{id}', [HRController::class, 'deleteArea'])->name('areas.delete');

        // Offices
        Route::get('/offices', [HRController::class, 'getOffices'])->name('offices.list');
        Route::post('/offices', [HRController::class, 'storeOffice'])->name('offices.store');
        Route::put('/offices/{id}', [HRController::class, 'updateOffice'])->name('offices.update');
        Route::delete('/offices/{id}', [HRController::class, 'deleteOffice'])->name('offices.delete');

        // Positions
        Route::get('/positions', [HRController::class, 'getPositions'])->name('positions.list');
        Route::post('/positions', [HRController::class, 'storePosition'])->name('positions.store');
        Route::put('/positions/{id}', [HRController::class, 'updatePosition'])->name('positions.update');
        Route::delete('/positions/{id}', [HRController::class, 'deletePosition'])->name('positions.delete');

        // Contract Types
        Route::get('/contract-types', [HRController::class, 'getContractTypes'])->name('contract-types.list');
        Route::post('/contract-types', [HRController::class, 'storeContractType'])->name('contract-types.store');
        Route::put('/contract-types/{id}', [HRController::class, 'updateContractType'])->name('contract-types.update');
        Route::delete('/contract-types/{id}', [HRController::class, 'deleteContractType'])->name('contract-types.delete');

        // Summary
        Route::get('/summary', [HRController::class, 'getSummary'])->name('summary');
    });

    // User Management (Admin only)
    Route::prefix('users')->name('users.')->middleware('admin')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/list', [UserController::class, 'getUsers'])->name('list');
        Route::get('/roles', [UserController::class, 'getRoles'])->name('roles');
        Route::get('/areas', [UserController::class, 'getAreas'])->name('areas');
        Route::get('/positions', [UserController::class, 'getPositions'])->name('positions');
        Route::get('/summary', [UserController::class, 'getSummary'])->name('summary');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{id}', [UserController::class, 'getUser'])->name('show');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::patch('/{id}/password', [UserController::class, 'updatePassword'])->name('password.update');
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });
});
