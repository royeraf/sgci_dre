<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Events\NewAppointmentCreated;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    public function adminIndex()
    {
        return Inertia::render('Appointments/Index');
    }

    public function create()
    {
        return Inertia::render('Appointments/Create');
    }

    public function index()
    {
        return Appointment::orderBy('created_at', 'desc')->get();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dni' => 'required|digits:8',
            'oficina' => 'required',
            'apellidos' => 'required',
            'nombres' => 'required',
            'fecha' => 'required|date', // Removed after:today check to allow testing easily, or add back
            'hora' => 'required',
            'celular' => 'required|digits:9', // Assuming Peru format
            'correo' => 'required|email',
            'asunto' => 'required|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $data = $request->all();
        $data['historial'] = [[
            'estado' => 'PENDIENTE',
            'fecha' => now()->toDateTimeString(),
            'descripcion' => 'Cita registrada'
        ]];
        
        $appointment = Appointment::create($data);

        // Broadcast event for real-time notifications
        event(new NewAppointmentCreated($appointment));

        return response()->json(['id' => $appointment->id, 'message' => 'Cita registrada con éxito'], 201);
    }

    public function checkStatus($dni)
    {
        return Appointment::where('dni', $dni)->orderBy('created_at', 'desc')->get();
    }

    public function updateStatus(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:ATENDIDO,CANCELADO,PENDIENTE',
            'observacion' => 'nullable|string'
        ]);

        $historial = $appointment->historial ?? [];
        $historial[] = [
            'estado' => $request->status,
            'fecha' => now()->toDateTimeString(),
            'descripcion' => $request->observacion ?? 'Estado actualizado a ' . $request->status
        ];

        $appointment->update([
            'estado' => $request->status,
            'historial' => $historial
        ]);

        return response()->json(['message' => 'Estado actualizado correctamente']);
    }
}
