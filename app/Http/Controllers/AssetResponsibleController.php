<?php

namespace App\Http\Controllers;

use App\Models\AssetResponsible;
use App\Models\Employee;
use Illuminate\Http\Request;

class AssetResponsibleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AssetResponsible::with(['employee.person', 'area', 'office']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        return response()->json($query->get());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $employee = Employee::with('person')->find($request->employee_id);

        $responsible = AssetResponsible::firstOrCreate(
            ['employee_id' => $employee->id],
            [
                'nombre_original' => strtoupper($employee->full_name),
                'activo' => true,
            ]
        );

        return response()->json($responsible);
    }

    /**
     * Display the specified resource.
     */
    public function show(AssetResponsible $assetResponsible)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AssetResponsible $assetResponsible)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AssetResponsible $assetResponsible)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AssetResponsible $assetResponsible)
    {
        //
    }
}
