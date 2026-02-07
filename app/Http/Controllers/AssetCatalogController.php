<?php

namespace App\Http\Controllers;

use App\Models\AssetBrand;
use App\Models\AssetCategory;
use App\Models\AssetColor;
use App\Models\AssetOrigin;
use App\Models\AssetState;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetCatalogController extends Controller
{
    /**
     * Display the catalogs management page.
     */
    public function index()
    {
        return Inertia::render('Assets/Catalogs', [
            'brands' => AssetBrand::orderBy('nombre')->get(),
            'colors' => AssetColor::orderBy('nombre')->get(),
            'states' => AssetState::orderBy('orden')->get(),
            'origins' => AssetOrigin::orderBy('nombre')->get(),
            'categories' => AssetCategory::orderBy('nombre')->get(),
        ]);
    }

    // ===== MARCAS =====
    
    public function getBrands()
    {
        return response()->json(AssetBrand::orderBy('nombre')->get());
    }

    public function storeBrand(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:asset_brands,nombre',
            'activo' => 'boolean',
        ]);

        $brand = AssetBrand::create($validated);
        return response()->json($brand, 201);
    }

    public function updateBrand(Request $request, $id)
    {
        $brand = AssetBrand::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:asset_brands,nombre,' . $id,
            'activo' => 'boolean',
        ]);

        $brand->update($validated);
        return response()->json($brand);
    }

    public function deleteBrand($id)
    {
        $brand = AssetBrand::findOrFail($id);
        
        // Check if used
        if ($brand->assets()->exists()) {
            return response()->json(['error' => 'No se puede eliminar: hay bienes con esta marca'], 422);
        }
        
        $brand->delete();
        return response()->json(['message' => 'Marca eliminada correctamente']);
    }

    // ===== COLORES =====
    
    public function getColors()
    {
        return response()->json(AssetColor::orderBy('nombre')->get());
    }

    public function storeColor(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:asset_colors,nombre',
            'codigo_hex' => 'nullable|string|max:7',
            'activo' => 'boolean',
        ]);

        $color = AssetColor::create($validated);
        return response()->json($color, 201);
    }

    public function updateColor(Request $request, $id)
    {
        $color = AssetColor::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:asset_colors,nombre,' . $id,
            'codigo_hex' => 'nullable|string|max:7',
            'activo' => 'boolean',
        ]);

        $color->update($validated);
        return response()->json($color);
    }

    public function deleteColor($id)
    {
        $color = AssetColor::findOrFail($id);
        
        if ($color->assets()->exists()) {
            return response()->json(['error' => 'No se puede eliminar: hay bienes con este color'], 422);
        }
        
        $color->delete();
        return response()->json(['message' => 'Color eliminado correctamente']);
    }

    // ===== ESTADOS =====
    
    public function getStates()
    {
        return response()->json(AssetState::orderBy('orden')->get());
    }

    public function storeState(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:asset_states,nombre',
            'descripcion' => 'nullable|string',
            'orden' => 'integer',
            'activo' => 'boolean',
        ]);

        // Set orden if not provided
        if (!isset($validated['orden'])) {
            $validated['orden'] = AssetState::max('orden') + 1;
        }

        $state = AssetState::create($validated);
        return response()->json($state, 201);
    }

    public function updateState(Request $request, $id)
    {
        $state = AssetState::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:asset_states,nombre,' . $id,
            'descripcion' => 'nullable|string',
            'orden' => 'integer',
            'activo' => 'boolean',
        ]);

        $state->update($validated);
        return response()->json($state);
    }

    public function deleteState($id)
    {
        $state = AssetState::findOrFail($id);
        
        if ($state->movements()->exists()) {
            return response()->json(['error' => 'No se puede eliminar: hay movimientos con este estado'], 422);
        }
        
        $state->delete();
        return response()->json(['message' => 'Estado eliminado correctamente']);
    }

    // ===== ORÍGENES =====
    
    public function getOrigins()
    {
        return response()->json(AssetOrigin::orderBy('nombre')->get());
    }

    public function storeOrigin(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:asset_origins,nombre',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $origin = AssetOrigin::create($validated);
        return response()->json($origin, 201);
    }

    public function updateOrigin(Request $request, $id)
    {
        $origin = AssetOrigin::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:asset_origins,nombre,' . $id,
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $origin->update($validated);
        return response()->json($origin);
    }

    public function deleteOrigin($id)
    {
        $origin = AssetOrigin::findOrFail($id);
        
        if ($origin->assets()->exists()) {
            return response()->json(['error' => 'No se puede eliminar: hay bienes con este origen'], 422);
        }
        
        $origin->delete();
        return response()->json(['message' => 'Origen eliminado correctamente']);
    }

    // ===== CATEGORÍAS =====
    
    public function getCategories()
    {
        return response()->json(AssetCategory::orderBy('nombre')->get());
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:asset_categories,nombre',
        ]);

        $category = AssetCategory::create($validated);
        return response()->json($category, 201);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = AssetCategory::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:asset_categories,nombre,' . $id,
        ]);

        $category->update($validated);
        return response()->json($category);
    }

    public function deleteCategory($id)
    {
        $category = AssetCategory::findOrFail($id);
        
        if ($category->assets()->exists()) {
            return response()->json(['error' => 'No se puede eliminar: hay bienes con esta categoría'], 422);
        }
        
        $category->delete();
        return response()->json(['message' => 'Categoría eliminada correctamente']);
    }
}
