<?php

namespace App\Http\Controllers;

use App\Models\Occurrence;
use App\Models\EntryExit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with stats.
     */
    public function index()
    {
        $today = now()->toDateString();

        // Occurrences Stats
        $occurrences = [
            'total' => Occurrence::count(),
            'incidentes' => Occurrence::where('tipo', 'Incidente')->count(),
            'emergencias' => Occurrence::whereIn('tipo', ['Emergencia', 'Robo'])->count(),
            'rutinas' => Occurrence::where('tipo', 'Rutina')->count(),
        ];

        // Personnel Stats
        $personnel = [
            'total' => EntryExit::count(),
            'pending_return' => EntryExit::whereNull('hora_retorno')->count(),
            'today' => EntryExit::whereDate('fecha', $today)->count(),
        ];

        // Visitor Stats (Placeholder since model might not exist yet or is different)
        // Check if Staff or other model can be used, but usually it's a Visitor model.
        // For now, providing a structured placeholder.
        $visitors = [
            'total' => 0,
            'active' => 0,
            'today' => 0,
        ];

        return Inertia::render('Dashboard', [
            'stats' => [
                'occurrences' => $occurrences,
                'personnel' => $personnel,
                'visitors' => $visitors,
            ]
        ]);
    }
}
