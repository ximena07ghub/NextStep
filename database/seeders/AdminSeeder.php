<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // crea un administrador sin guardar claves en el código
        $email = strtolower(trim((string) env('ADMIN_EMAIL', '')));
        $password = (string) env('ADMIN_PASSWORD', '');
        $name = trim((string) env('ADMIN_NAME', 'Administrador'));

        Validator::make(compact('email', 'password', 'name'), [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:72'],
            'name' => ['required', 'string', 'max:100'],
        ])->validate();

        $existing = User::where('email', $email)->first();
        if ($existing) {
            if ($existing->role !== 'admin') {
                throw ValidationException::withMessages(['email' => 'El correo pertenece a otra cuenta.']);
            }
            $this->command?->info('El administrador ya existe; no se cambió la contraseña.');
            return;
        }

        User::create([
            'email' => $email,
            'first_name' => $name,
            'password' => $password,
            'role' => 'admin',
        ]);
        $this->command?->info('Administrador creado.');
    }
}
