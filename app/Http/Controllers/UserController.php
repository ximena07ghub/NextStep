<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // crear un usuario (estudiante o empresa)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|string|in:student,company',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            
            // validaciones condicionales para estudiantes
            'first_name' => 'required_if:role,student|nullable|string|max:255',
            'paternal_last_name' => 'required_if:role,student|nullable|string|max:255',
            'maternal_last_name' => 'nullable|string|max:255',
            'birth_date' => 'required_if:role,student|nullable|date',

            // validaciones condicionales para empresas
            'company_name' => 'required_if:role,company|nullable|string|max:255',
            'rfc' => 'required_if:role,company|nullable|string|max:13',
            'sector' => 'required_if:role,company|nullable|string|max:255',
            'state' => 'required_if:role,company|nullable|string|max:255',
            'city' => 'required_if:role,company|nullable|string|max:255',
        ]);

        // el cast 'hashed' en el modelo user encripta la contraseña automáticamente
        $user = User::create($validated);
        
        return response()->json($user, 201);
    }

    // obtener todos los usuarios
    public function index()
    {
        return response()->json(User::paginate(20), 200);
    }

    // obtener un solo usuario por id
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user, 200);
    }

    // actualizar usuario
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'paternal_last_name' => 'sometimes|required|string|max:255',
            'maternal_last_name' => 'nullable|string|max:255',
            'password' => 'sometimes|required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'sometimes|required|string|max:255',
            'state' => 'sometimes|required|string|max:255',
            'city' => 'sometimes|required|string|max:255',
        ]);

        $user->update($validated);
        
        return response()->json($user, 200);
    }

    // eliminar usuario
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        app(\App\Services\AccountDeletionService::class)->delete($user);
        
        return response()->json(['message' => 'Usuario eliminado'], 200);
    }
}