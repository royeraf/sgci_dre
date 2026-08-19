<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleCommission;
use App\Models\VehicleMaintenance;
use App\Models\VehicleHandover;
use App\Models\VehicleServiceRequirement;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class VehicleController extends Controller
{
    /**
     * Display the vehicle control page
     */
    public function index()
    {
        $activeEmployeesQuery = fn () => Employee::with(['person', 'position'])
            ->where('estado', 'ACTIVO')
            ->whereHas('person', function ($q) {
                $q->whereNotNull('nombres')
                    ->whereNotNull('apellidos')
                    ->where('nombres', '!=', '')
                    ->where('apellidos', '!=', '');
            });

        $mapEmployee = fn ($emp) => [
            'id' => $emp->id,
            'nombre_completo' => $emp->person->nombre_full,
            'dni' => $emp->dni,
            'cargo' => $emp->position?->nombre,
        ];

        $user = Auth::user();

        return Inertia::render('Vehicles/Index', [
            'drivers' => $activeEmployeesQuery()
                ->whereHas('position', fn ($q) => $q->where('nombre', 'CHOFER II'))
                ->get()->map($mapEmployee)->sortBy('nombre_completo')->values(),
            'canAuthorize' => $user->puedeAutorizarSalidaVehicular(),
            'currentEmployeeId' => $user->employee?->id,
        ]);
    }

    // ========================================
    // VEHICLES (INVENTORY)
    // ========================================

    /**
     * Get all vehicles
     */
    public function getVehicles()
    {
        $vehicles = Vehicle::orderBy('placa')->get();
        return response()->json($vehicles);
    }

    /**
     * Create a new vehicle
     */
    public function storeVehicle(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|string|max:100',
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:100',
            'placa' => 'required|string|max:20|unique:vehicles,placa',
            'anio' => 'nullable|string|max:10',
            'motor' => 'nullable|string|max:100',
            'chasis' => 'nullable|string|max:100',
            'cilindros' => 'nullable|string|max:10',
            'asientos' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:50',
            'estado' => 'nullable|string|max:50',
            'kilometraje' => 'nullable|string|max:20',
            'combustible' => 'nullable|string|max:50',
            'fecha_soat' => 'nullable|date',
            'fecha_revision' => 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        $vehicle = Vehicle::create($validated);

        return response()->json([
            'message' => 'Vehículo registrado correctamente',
            'vehicle' => $vehicle
        ], 201);
    }

    /**
     * Update a vehicle
     */
    public function updateVehicle(Request $request, string $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $validated = $request->validate([
            'tipo' => 'sometimes|string|max:100',
            'marca' => 'sometimes|string|max:100',
            'modelo' => 'sometimes|string|max:100',
            'placa' => 'sometimes|string|max:20|unique:vehicles,placa,' . $id,
            'anio' => 'nullable|string|max:10',
            'motor' => 'nullable|string|max:100',
            'chasis' => 'nullable|string|max:100',
            'cilindros' => 'nullable|string|max:10',
            'asientos' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:50',
            'estado' => 'nullable|string|max:50',
            'kilometraje' => 'nullable|string|max:20',
            'combustible' => 'nullable|string|max:50',
            'fecha_soat' => 'nullable|date',
            'fecha_revision' => 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        $vehicle->update($validated);

        return response()->json([
            'message' => 'Vehículo actualizado correctamente',
            'vehicle' => $vehicle
        ]);
    }

    /**
     * Delete a vehicle
     */
    public function deleteVehicle(string $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();

        return response()->json([
            'message' => 'Vehículo eliminado correctamente'
        ]);
    }

    // ========================================
    // COMMISSIONS
    // ========================================

    /**
     * Get all commissions
     */
    public function getCommissions()
    {
        $user = Auth::user();
        $canAuthorize = $user->puedeAutorizarSalidaVehicular();
        $currentEmployeeId = $user->employee?->id;

        $commissions = VehicleCommission::with([
                'vehicle',
                'solicitanteEmployee.person',
                'conductorEmployee.person',
                'autorizadorEmployee.person',
            ])
            ->orderBy('dia', 'desc')
            ->orderBy('hora', 'desc')
            ->get()
            ->map(function ($commission) use ($canAuthorize, $currentEmployeeId) {
                return [
                    'id' => $commission->id,
                    'numero' => $commission->numero,
                    'anio' => $commission->anio,
                    'solicitante_employee_id' => $commission->solicitante_employee_id,
                    'solicitante' => $commission->solicitante_nombre,
                    'dia' => $commission->dia->format('Y-m-d'),
                    'hora' => $commission->hora,
                    'lugar' => $commission->lugar,
                    'referencia' => $commission->referencia,
                    'motivo' => $commission->motivo,
                    'usuarios' => $commission->usuarios,
                    'vehicle_id' => $commission->vehicle_id,
                    'placa' => $commission->vehicle?->placa ?? 'N/A',
                    'marca' => $commission->vehicle?->marca ?? '',
                    'modelo' => $commission->vehicle?->modelo ?? '',
                    'conductor_employee_id' => $commission->conductor_employee_id,
                    'conductor' => $commission->conductor_nombre,
                    'autorizado_por' => $commission->autorizado_por,
                    'autorizado_por_nombre' => $commission->autorizado_por_nombre,
                    'fecha_autorizacion' => optional($commission->fecha_autorizacion)->format('Y-m-d H:i:s'),
                    'comentario_autorizacion' => $commission->comentario_autorizacion,
                    'fecha_confirmacion_conductor' => optional($commission->fecha_confirmacion_conductor)->format('Y-m-d H:i:s'),
                    'hora_salida' => $commission->hora_salida,
                    'hora_regreso' => $commission->hora_regreso,
                    'km_salida' => $commission->km_salida,
                    'km_retorno' => $commission->km_retorno,
                    'total_km_recorrido' => $commission->total_km_recorrido,
                    'combustible' => $commission->combustible,
                    'pnro' => $commission->pnro,
                    'estado' => $commission->estado,
                    'created_at' => $commission->created_at->format('Y-m-d H:i:s'),
                    'can_authorize' => $canAuthorize && $commission->necesitaAutorizacion(),
                    'can_confirm' => $currentEmployeeId
                        && $currentEmployeeId === $commission->conductor_employee_id
                        && $commission->necesitaConfirmacionConductor(),
                ];
            });

        return response()->json($commissions);
    }

    /**
     * Create a new commission (Autorización Salida de Vehículos)
     */
    public function storeCommission(Request $request)
    {
        $solicitanteEmployeeId = Auth::user()->employee?->id;

        if (!$solicitanteEmployeeId) {
            return response()->json([
                'message' => 'Su usuario no está vinculado a un empleado y no puede solicitar una salida vehicular.',
            ], 422);
        }

        $validated = $request->validate([
            'dia' => 'required|date',
            'hora' => 'required',
            'lugar' => 'required|string|max:255',
            'referencia' => 'nullable|string|max:255',
            'motivo' => 'nullable|string',
            'usuarios' => 'nullable|string',
            'vehicle_id' => 'nullable|uuid|exists:vehicles,id',
            'conductor_employee_id' => 'required|uuid|exists:employees,id',
            'combustible' => 'nullable|string|in:Gasolina,Diesel,GLP,GNV',
            'pnro' => 'nullable|string|max:100',
        ]);

        $validated['solicitante_employee_id'] = $solicitanteEmployeeId;
        $validated['estado'] = 'PENDIENTE';
        $validated['anio'] = (int) date('Y', strtotime($validated['dia']));
        $validated['numero'] = VehicleCommission::nextNumero($validated['anio']);

        $commission = VehicleCommission::create($validated);

        return response()->json([
            'message' => 'Autorización de salida registrada correctamente',
            'commission' => $commission
        ], 201);
    }

    /**
     * Update a commission (Autorización Salida de Vehículos)
     */
    public function updateCommission(Request $request, string $id)
    {
        $commission = VehicleCommission::findOrFail($id);

        $validated = $request->validate([
            'dia' => 'sometimes|date',
            'hora' => 'sometimes',
            'lugar' => 'sometimes|string|max:255',
            'referencia' => 'nullable|string|max:255',
            'motivo' => 'nullable|string',
            'usuarios' => 'nullable|string',
            'vehicle_id' => 'nullable|uuid|exists:vehicles,id',
            'conductor_employee_id' => 'sometimes|uuid|exists:employees,id',
            'hora_salida' => 'nullable',
            'hora_regreso' => 'nullable',
            'km_salida' => 'nullable|integer|min:0',
            'km_retorno' => [
                'nullable',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($request, $commission) {
                    $kmSalida = $request->has('km_salida') ? $request->input('km_salida') : $commission->km_salida;
                    if (is_numeric($value) && is_numeric($kmSalida) && (int)$value < (int)$kmSalida) {
                        $fail('El kilometraje de retorno debe ser mayor o igual al de salida.');
                    }
                }
            ],
            'combustible' => 'nullable|string|in:Gasolina,Diesel,GLP,GNV',
            'pnro' => 'nullable|string|max:100',
            // Solo la cancelación se hace por esta vía; autorizar/rechazar/confirmar
            // tienen sus propios endpoints para no saltarse el flujo de aprobación.
            'estado' => 'nullable|string|in:CANCELADA',
        ]);

        if (!empty($validated['hora_salida']) && $commission->estado !== 'CONFIRMADA') {
            return response()->json([
                'message' => 'La salida solo puede registrarse una vez que el conductor haya confirmado la autorización.',
            ], 422);
        }

        if (!empty($validated['hora_regreso']) && $commission->estado !== 'EN_COMISION' && empty($validated['hora_salida'])) {
            return response()->json([
                'message' => 'El regreso solo puede registrarse una vez que la comisión esté en curso.',
            ], 422);
        }

        // Auto-update estado based on times
        if (!empty($validated['hora_regreso'])) {
            $validated['estado'] = 'COMPLETADA';
        } elseif (!empty($validated['hora_salida'])) {
            $validated['estado'] = 'EN_COMISION';
        }

        $commission->update($validated);

        return response()->json([
            'message' => 'Autorización de salida actualizada correctamente',
            'commission' => $commission
        ]);
    }

    /**
     * Authorize a pending commission (Autorización Salida de Vehículos).
     */
    public function authorizeCommission(Request $request, string $id)
    {
        if (!Auth::user()->puedeAutorizarSalidaVehicular()) {
            return response()->json(['message' => 'No tiene permisos para autorizar salidas vehiculares.'], 403);
        }

        $commission = VehicleCommission::findOrFail($id);

        if (!$commission->necesitaAutorizacion()) {
            return response()->json(['message' => 'La solicitud ya fue procesada.'], 422);
        }

        $commission->update([
            'estado' => 'AUTORIZADA',
            'autorizado_por' => Auth::user()->employee?->id,
            'fecha_autorizacion' => now(),
            'comentario_autorizacion' => $request->input('comentario'),
        ]);

        return response()->json([
            'message' => 'Salida vehicular autorizada correctamente.',
            'commission' => $commission->fresh(['vehicle', 'solicitanteEmployee.person', 'conductorEmployee.person', 'autorizadorEmployee.person']),
        ]);
    }

    /**
     * Reject a pending commission (Autorización Salida de Vehículos).
     */
    public function rejectCommission(Request $request, string $id)
    {
        if (!Auth::user()->puedeAutorizarSalidaVehicular()) {
            return response()->json(['message' => 'No tiene permisos para autorizar salidas vehiculares.'], 403);
        }

        $request->validate([
            'comentario' => 'required|string|max:500',
        ], [
            'comentario.required' => 'Debe indicar el motivo del rechazo.',
        ]);

        $commission = VehicleCommission::findOrFail($id);

        if (!$commission->necesitaAutorizacion()) {
            return response()->json(['message' => 'La solicitud ya fue procesada.'], 422);
        }

        $commission->update([
            'estado' => 'RECHAZADA',
            'autorizado_por' => Auth::user()->employee?->id,
            'fecha_autorizacion' => now(),
            'comentario_autorizacion' => $request->comentario,
        ]);

        return response()->json([
            'message' => 'Salida vehicular rechazada.',
            'commission' => $commission->fresh(['vehicle', 'solicitanteEmployee.person', 'conductorEmployee.person', 'autorizadorEmployee.person']),
        ]);
    }

    /**
     * Driver confirmation of an authorized commission (Autorización Salida de Vehículos).
     */
    public function confirmCommissionByConductor(string $id)
    {
        $commission = VehicleCommission::findOrFail($id);
        $employeeId = Auth::user()->employee?->id;

        if (!$employeeId || $employeeId !== $commission->conductor_employee_id) {
            return response()->json(['message' => 'Solo el conductor asignado puede confirmar esta salida.'], 403);
        }

        if (!$commission->necesitaConfirmacionConductor()) {
            return response()->json(['message' => 'La solicitud no está pendiente de confirmación del conductor.'], 422);
        }

        $commission->update([
            'estado' => 'CONFIRMADA',
            'fecha_confirmacion_conductor' => now(),
        ]);

        return response()->json([
            'message' => 'Salida confirmada por el conductor.',
            'commission' => $commission->fresh(['vehicle', 'solicitanteEmployee.person', 'conductorEmployee.person', 'autorizadorEmployee.person']),
        ]);
    }

    /**
     * Generate the printable PDF for an Autorización Salida de Vehículos
     */
    public function commissionPdf(string $id)
    {
        $commission = VehicleCommission::with(['vehicle', 'solicitanteEmployee.person', 'conductorEmployee.person', 'autorizadorEmployee.person'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.vehicle_exit_authorization', [
            'commission' => $commission,
        ]);

        return $pdf->stream("autorizacion_salida_{$commission->numero}_{$commission->anio}.pdf");
    }

    // ========================================
    // MAINTENANCE
    // ========================================

    /**
     * Get all maintenance expenses
     */
    public function getMaintenances()
    {
        $maintenances = VehicleMaintenance::with('vehicle')
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function ($maintenance) {
                return [
                    'id' => $maintenance->id,
                    'vehicle_id' => $maintenance->vehicle_id,
                    'vehicle_name' => $maintenance->vehicle?->display_name ?? 'N/A',
                    'fecha' => $maintenance->fecha->format('Y-m-d'),
                    'factura' => $maintenance->factura,
                    'kilometraje' => $maintenance->kilometraje,
                    'orden_sc' => $maintenance->orden_sc,
                    'proveedor' => $maintenance->proveedor,
                    'detalle' => $maintenance->detalle,
                    'costo' => $maintenance->costo,
                    'vigilante' => $maintenance->vigilante,
                    'responsable' => $maintenance->responsable,
                    'created_at' => $maintenance->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json($maintenances);
    }

    /**
     * Create a new maintenance expense
     */
    public function storeMaintenance(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|uuid|exists:vehicles,id',
            'fecha' => 'required|date',
            'factura' => 'nullable|string|max:100',
            'kilometraje' => 'nullable|string|max:20',
            'orden_sc' => 'nullable|string|max:100',
            'proveedor' => 'nullable|string|max:255',
            'detalle' => 'required|string',
            'costo' => 'required|numeric|min:0',
            'vigilante' => 'nullable|string|max:255',
            'responsable' => 'nullable|string|max:255',
        ]);

        $maintenance = VehicleMaintenance::create($validated);

        return response()->json([
            'message' => 'Gasto de mantenimiento registrado correctamente',
            'maintenance' => $maintenance
        ], 201);
    }

    // ========================================
    // HANDOVERS
    // ========================================

    /**
     * Get all handovers
     */
    public function getHandovers()
    {
        $handovers = VehicleHandover::orderBy('fecha', 'desc')
            ->get()
            ->map(function ($handover) {
                return [
                    'id' => $handover->id,
                    'fecha' => $handover->fecha->format('Y-m-d'),
                    'entidad' => $handover->entidad,
                    'denominacion' => $handover->denominacion,
                    'placa' => $handover->placa,
                    'color' => $handover->color,
                    'kilometraje' => $handover->kilometraje,
                    'carroceria' => $handover->carroceria,
                    'n_motor' => $handover->n_motor,
                    'sistemas' => $handover->sistemas,
                    'abastecimiento' => $handover->abastecimiento,
                    'recepciona' => $handover->recepciona,
                    'entrega' => $handover->entrega,
                    'created_at' => $handover->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json($handovers);
    }

    /**
     * Create a new handover
     */
    public function storeHandover(Request $request)
    {
        $validated = $request->validate([
            'fecha' => 'required|date',
            'entidad' => 'nullable|string|max:255',
            'denominacion' => 'nullable|string|max:255',
            'placa' => 'required|string|max:20',
            'color' => 'nullable|string|max:50',
            'kilometraje' => 'required|string|max:20',
            'carroceria' => 'nullable|string|max:100',
            'n_motor' => 'nullable|string|max:100',
            'sistemas' => 'nullable|array',
            'abastecimiento' => 'nullable|string|max:255',
            'recepciona' => 'required|string|max:255',
            'entrega' => 'nullable|string|max:255',
        ]);

        $handover = VehicleHandover::create($validated);

        return response()->json([
            'message' => 'Acta de entrega registrada correctamente',
            'handover' => $handover
        ], 201);
    }

    // ========================================
    // SERVICE REQUIREMENTS
    // ========================================

    /**
     * Get all service requirements
     */
    public function getServiceRequirements()
    {
        $requirements = VehicleServiceRequirement::with('vehicle')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($req) {
                return [
                    'id' => $req->id,
                    'conductor' => $req->conductor,
                    'vehicle_id' => $req->vehicle_id,
                    'vehicle_name' => $req->vehicle?->display_name ?? 'N/A',
                    'estado_vehiculo' => $req->estado_vehiculo,
                    'estado_motor' => $req->estado_motor,
                    'encendido_electrico' => $req->encendido_electrico,
                    'transmision' => $req->transmision,
                    'pintura_carroceria' => $req->pintura_carroceria,
                    'llantas' => $req->llantas,
                    'herramientas' => $req->herramientas,
                    'implementos_aseo' => $req->implementos_aseo,
                    'comisiones_realizadas' => $req->comisiones_realizadas,
                    'motivo' => $req->motivo,
                    'created_at' => $req->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json($requirements);
    }

    /**
     * Create a new service requirement
     */
    public function storeServiceRequirement(Request $request)
    {
        $validated = $request->validate([
            'conductor' => 'required|string|max:255',
            'vehicle_id' => 'required|uuid|exists:vehicles,id',
            'estado_vehiculo' => 'nullable|string|max:100',
            'estado_motor' => 'nullable|string|max:100',
            'encendido_electrico' => 'nullable|string|max:100',
            'transmision' => 'nullable|string|max:100',
            'pintura_carroceria' => 'nullable|string|max:100',
            'llantas' => 'nullable|string|max:100',
            'herramientas' => 'nullable|string|max:100',
            'implementos_aseo' => 'nullable|string|max:100',
            'comisiones_realizadas' => 'nullable|string',
            'motivo' => 'required|string',
        ]);

        $requirement = VehicleServiceRequirement::create($validated);

        return response()->json([
            'message' => 'Requerimiento de servicio registrado correctamente',
            'requirement' => $requirement
        ], 201);
    }

    // ========================================
    // SUMMARY
    // ========================================

    /**
     * Get vehicle control summary
     */
    public function getSummary()
    {
        $totalVehicles = Vehicle::count();
        $operativeVehicles = Vehicle::where('estado', 'Operativo')->count();
        $inMaintenanceVehicles = Vehicle::where('estado', 'En Mantenimiento')->count();
        
        $pendingCommissions = VehicleCommission::where('estado', 'PENDIENTE')->count();
        $activeCommissions = VehicleCommission::where('estado', 'EN_COMISION')->count();
        
        $todayCommissions = VehicleCommission::whereDate('dia', Carbon::today())->count();
        
        $monthlyMaintenanceCost = VehicleMaintenance::whereMonth('fecha', Carbon::now()->month)
            ->whereYear('fecha', Carbon::now()->year)
            ->sum('costo');

        return response()->json([
            'total_vehicles' => $totalVehicles,
            'operative_vehicles' => $operativeVehicles,
            'in_maintenance_vehicles' => $inMaintenanceVehicles,
            'pending_commissions' => $pendingCommissions,
            'active_commissions' => $activeCommissions,
            'today_commissions' => $todayCommissions,
            'monthly_maintenance_cost' => $monthlyMaintenanceCost,
        ]);
    }
}
