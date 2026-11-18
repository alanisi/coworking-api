<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SpaceType;
use Illuminate\Http\Request;

class SpaceTypeController extends Controller
{
    public function index()
    {
        return $this->successResponse(SpaceType::all(), 'Tipos de espacio');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|unique:space_types,name',
            'slug'        => 'required|string|unique:space_types,slug',
            'description' => 'nullable|string',
        ]);

        return $this->successResponse(SpaceType::create($validated), 'Tipo de espacio creado', 201);
    }

    public function update(Request $request, SpaceType $spaceType)
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|unique:space_types,name,' . $spaceType->id,
            'slug'        => 'sometimes|string|unique:space_types,slug,' . $spaceType->id,
            'description' => 'nullable|string',
        ]);

        $spaceType->update($validated);
        return $this->successResponse($spaceType, 'Tipo de espacio actualizado');
    }

    public function destroy(SpaceType $spaceType)
    {
        $spaceType->delete();
        return $this->successResponse(null, 'Tipo de espacio eliminado');
    }
}