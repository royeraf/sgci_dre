<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleCommission;
use App\Models\VehicleMaintenance;
use App\Models\VehicleHandover;
use App\Models\VehicleServiceRequirement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class VehicleController extends Controller
{
    /**
     * Display the vehicle control page
     */
    public function index()
    {
        return Inertia::render('Vehicles/Index');
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
        $commissions = VehicleCommission::with('vehicle')
            ->orderBy('dia', 'desc')
            ->orderBy('hora', 'desc')
            ->get()
            ->map(function ($commission) {
                return [
                    'id' => $commission->id,
                    'dependencia' => $commission->dependencia,
                    'dia' => $commission->dia->format('Y-m-d'),
                    'hora' => $commission->hora,
                    'lugar' => $commission->lugar,
                    'motivo' => $commission->motivo,
                    'usuarios' => $commission->usuarios,
                    'vehicle_id' => $commission->vehicle_id,
                    'placa' => $commission->vehicle?->placa ?? 'N/A',
                    'marca' => $commission->vehicle?->marca ?? '',
                    'chofer' => $commission->chofer,
                    'hora_salida' => $commission->hora_salida,
                    'hora_regreso' => $commission->hora_regreso,
                    'estado' => $commission->estado,
                    'created_at' => $commission->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json($commissions);
    }

    /**
     * Create a new commission
     */
    public function storeCommission(Request $request)
    {
        $validated = $request->validate([
            'dependencia' => 'required|string|max:255',
            'dia' => 'required|date',
            'hora' => 'required',
            'lugar' => 'required|string|max:255',
            'motivo' => 'nullable|string',
            'usuarios' => 'nullable|string',
            'vehicle_id' => 'nullable|uuid|exists:vehicles,id',
            'chofer' => 'required|string|max:255',
        ]);

        $validated['estado'] = 'PENDIENTE';

        $commission = VehicleCommission::create($validated);

        return response()->json([
            'message' => 'Comisión registrada correctamente',
            'commission' => $commission
        ], 201);
    }

    /**
     * Update a commission
     */
    public function updateCommission(Request $request, string $id)
    {
        $commission = VehicleCommission::findOrFail($id);

        $validated = $request->validate([
            'dependencia' => 'sometimes|string|max:255',
            'dia' => 'sometimes|date',
            'hora' => 'sometimes',
            'lugar' => 'sometimes|string|max:255',
            'motivo' => 'nullable|string',
            'usuarios' => 'nullable|string',
            'vehicle_id' => 'nullable|uuid|exists:vehicles,id',
            'chofer' => 'sometimes|string|max:255',
            'hora_salida' => 'nullable',
            'hora_regreso' => 'nullable',
            'estado' => 'nullable|string|max:50',
        ]);

        // Auto-update estado based on times
        if (isset($validated['hora_regreso']) && $validated['hora_regreso']) {
            $validated['estado'] = 'COMPLETADA';
        } elseif (isset($validated['hora_salida']) && $validated['hora_salida']) {
            $validated['estado'] = 'EN_COMISION';
        }

        $commission->update($validated);

        return response()->json([
            'message' => 'Comisión actualizada correctamente',
            'commission' => $commission
        ]);
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
