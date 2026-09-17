<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class PlanillaController extends Controller
{
    /**
     * Página principal del apartado de Planillas y Remuneraciones.
     *
     * Shell del módulo: expone las pestañas (planillas, boletas, conceptos,
     * descuentos, aportaciones, tardanzas). La lógica de negocio de cada
     * pestaña se conectará aquí conforme se habiliten sus endpoints.
     */
    public function index()
    {
        return Inertia::render('Planillas/Index');
    }
}
