<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return $this->successResponse(User::with('roles')->get(), 'Listado de usuarios');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        $user->assignRole($validated['role']);

        return $this->successResponse($user->load('roles'), 'Usuario creado correctamente', 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'role'  => 'sometimes|exists:roles,name',
        ]);

        $user->update($request->only(['name', 'email']));
        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return $this->successResponse($user->load('roles'), 'Usuario actualizado correctamente');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return $this->successResponse(null, 'Usuario eliminado correctamente');
    }
}
