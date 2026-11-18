<?php

namespace Database\Seeders;

use App\Models\Space;
use App\Models\SpaceType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Permisos
        $permissions = [
            'spaces.manage',
            'users.manage',
            'roles.manage',
            'reservations.view_all',
            'reservations.approve',
            'reservations.complete',
            'reservations.create',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // 2. Roles y asignación de permisos
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $staffRole = Role::create(['name' => 'staff']);
        $staffRole->givePermissionTo([
            'reservations.view_all',
            'reservations.approve',
            'reservations.complete',
        ]);

        $clientRole = Role::create(['name' => 'client']);
        $clientRole->givePermissionTo(['reservations.create']);

        // 3. Usuarios de prueba
        $admin = User::create([
            'name' => 'Administrador General',
            'email' => 'admin@coworking.com',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole($adminRole);

        $staff = User::create([
            'name' => 'Recepción Staff',
            'email' => 'staff@coworking.com',
            'password' => Hash::make('password123'),
        ]);
        $staff->assignRole($staffRole);

        $client = User::create([
            'name' => 'Juan Pérez (Cliente)',
            'email' => 'cliente@coworking.com',
            'password' => Hash::make('password123'),
        ]);
        $client->assignRole($clientRole);

        // 4. Tipos de espacio y Espacios de ejemplo
        $roomType = SpaceType::create([
            'name' => 'Sala de Reuniones',
            'slug' => 'meeting_room',
            'description' => 'Salas equipadas con proyector y pizarra para equipos.',
        ]);

        $deskType = SpaceType::create([
            'name' => 'Escritorio Flexible',
            'slug' => 'hot_desk',
            'description' => 'Puesto individual en área compartida.',
        ]);

        $boothType = SpaceType::create([
            'name' => 'Cabina Privada',
            'slug' => 'private_booth',
            'description' => 'Cabina insonorizada para videollamadas o trabajo enfocado.',
        ]);

        Space::create([
            'space_type_id' => $roomType->id,
            'name' => 'Sala de Reuniones A',
            'floor' => 2,
            'capacity' => 10,
            'price_per_hour' => 45.00,
            'is_active' => true,
        ]);

        Space::create([
            'space_type_id' => $deskType->id,
            'name' => 'Escritorio Flexible 01',
            'floor' => 1,
            'capacity' => 1,
            'price_per_hour' => 12.50,
            'is_active' => true,
        ]);

        Space::create([
            'space_type_id' => $boothType->id,
            'name' => 'Cabina Ejecutiva B',
            'floor' => 3,
            'capacity' => 2,
            'price_per_hour' => 25.00,
            'is_active' => true,
        ]);
    }
}