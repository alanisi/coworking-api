<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Space;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    public function index(Request $request)
    {
        $query = Space::with('spaceType')->active();

        if ($request->filled('space_type_id')) {
            $query->where('space_type_id', $request->space_type_id);
        }

        if ($request->filled('capacity')) {
            $query->where('capacity', '>=', $request->capacity);
        }

        if ($request->filled(['start_time', 'end_time'])) {
            $start = $request->start_time;
            $end = $request->end_time;
            $query->whereDoesntHave('reservations', function ($q) use ($start, $end) {
                $q->overlapping($start, $end);
            });
        }

        return $this->successResponse($query->get(), 'Catálogo de espacios disponibles');
    }

    public function occupancyByDate(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $date = $request->date;

        $spaces = Space::with(['spaceType', 'reservations' => function ($q) use ($date) {
            $q->whereDate('start_time', $date)
              ->whereIn('status', ['pending', 'confirmed', 'completed'])
              ->with('user:id,name,email');
        }])->get();

        return $this->successResponse($spaces, "Ocupación de espacios para el día {$date}");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'space_type_id'  => 'required|exists:space_types,id',
            'name'           => 'required|string|max:255',
            'floor'          => 'required|integer',
            'capacity'       => 'required|integer|min:1',
            'price_per_hour' => 'required|numeric|min:0',
            'is_active'      => 'boolean',
        ]);

        $space = Space::create($validated);
        return $this->successResponse($space->load('spaceType'), 'Espacio creado correctamente', 201);
    }

    public function show(Space $space)
    {
        return $this->successResponse($space->load('spaceType'), 'Detalle del espacio');
    }

    public function update(Request $request, Space $space)
    {
        $validated = $request->validate([
            'space_type_id'  => 'sometimes|exists:space_types,id',
            'name'           => 'sometimes|string|max:255',
            'floor'          => 'sometimes|integer',
            'capacity'       => 'sometimes|integer|min:1',
            'price_per_hour' => 'sometimes|numeric|min:0',
            'is_active'      => 'sometimes|boolean',
        ]);

        $space->update($validated);
        return $this->successResponse($space->load('spaceType'), 'Espacio actualizado correctamente');
    }

    public function destroy(Space $space)
    {
        $space->delete();
        return $this->successResponse(null, 'Espacio eliminado correctamente');
    }
}