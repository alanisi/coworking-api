<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Space;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function myReservations(Request $request)
    {
        $reservations = $request->user()
            ->reservations()
            ->with('space.spaceType')
            ->latest('start_time')
            ->get();

        return $this->successResponse($reservations, 'Historial de tus reservas');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'space_id'   => 'required|exists:spaces,id',
            'start_time' => 'required|date|after:now',
            'end_time'   => 'required|date|after:start_time',
            'notes'      => 'nullable|string|max:500',
        ], [
            'start_time.after' => 'La fecha de inicio debe ser posterior a la hora actual.',
            'end_time.after'   => 'La fecha de fin debe ser posterior a la de inicio.',
        ]);

        $space = Space::active()->find($validated['space_id']);
        if (!$space) {
            return $this->errorResponse('El espacio seleccionado no está disponible o se encuentra inactivo.', 422);
        }

        // Regla 1: No permitir solapamiento de horario[cite: 4]
        $hasOverlap = Reservation::where('space_id', $space->id)
            ->overlapping($validated['start_time'], $validated['end_time'])
            ->exists();

        if ($hasOverlap) {
            return $this->errorResponse('El espacio ya está reservado en ese horario.', 422);
        }

        // Regla 2: Máximo 2 reservas pending/confirmed simultáneas a futuro por cliente[cite: 4]
        $activeFutureCount = $request->user()
            ->reservations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('end_time', '>', now())
            ->count();

        if ($activeFutureCount >= 2) {
            return $this->errorResponse('No puedes tener más de 2 reservas activas o pendientes a futuro.', 422);
        }

        $reservation = Reservation::create([
            'user_id'    => $request->user()->id,
            'space_id'   => $space->id,
            'start_time' => $validated['start_time'],
            'end_time'   => $validated['end_time'],
            'status'     => 'pending',
            'notes'      => $validated['notes'] ?? null,
        ]);

        return $this->successResponse($reservation->load('space'), 'Reserva creada correctamente', 201);
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        if ($reservation->user_id !== $request->user()->id) {
            return $this->errorResponse('Solo puedes cancelar tus propias reservas.', 403);
        }

        if ($reservation->start_time->isPast()) {
            return $this->errorResponse('No puedes cancelar una reserva que ya ha iniciado o finalizado.', 422);
        }

        if (in_array($reservation->status, ['cancelled', 'completed'])) {
            return $this->errorResponse('La reserva ya está cancelada o completada.', 422);
        }

        $reservation->update(['status' => 'cancelled']);
        return $this->successResponse($reservation, 'Reserva cancelada correctamente');
    }

    public function pending()
    {
        $reservations = Reservation::with(['user:id,name,email', 'space.spaceType'])
            ->where('status', 'pending')
            ->orderBy('start_time')
            ->get();

        return $this->successResponse($reservations, 'Listado de reservas pendientes');
    }

    public function approveOrReject(Request $request, Reservation $reservation)
    {
        $request->validate([
            'action' => 'required|in:confirm,reject',
        ]);

        if ($reservation->status !== 'pending') {
            return $this->errorResponse('Solo se pueden procesar reservas en estado pendiente.', 422);
        }

        $newStatus = $request->action === 'confirm' ? 'confirmed' : 'cancelled';
        $reservation->update(['status' => $newStatus]);

        $msg = $newStatus === 'confirmed' ? 'Reserva confirmada correctamente' : 'Reserva rechazada correctamente';
        return $this->successResponse($reservation, $msg);
    }

    public function complete(Reservation $reservation)
    {
        if ($reservation->status !== 'confirmed') {
            return $this->errorResponse('Solo se pueden completar reservas previamente confirmadas.', 422);
        }

        $reservation->update(['status' => 'completed']);
        return $this->successResponse($reservation, 'Reserva marcada como completada');
    }
}