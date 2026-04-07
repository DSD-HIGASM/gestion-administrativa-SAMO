<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        Permission::create(['name' => 'dev-config']);
        Permission::create(['name' => 'config']);
        Permission::create(['name' => 'ver-nomenclador']);
        Permission::create(['name' => 'ver-gestion-guardia']);
        Permission::create(['name' => 'ver-gestion-ambulatorio']);
        Permission::create(['name' => 'facturar-guardia']);
        Permission::create(['name' => 'facturar-ambulatorio-baja']);
        Permission::create(['name' => 'facturar-ambulatorio-alta']);

        $devRole = Role::create(['name' => 'dev']);
        $devRole->givePermissionTo(Permission::all());

        $bossRole = Role::create(['name' => 'jefe']);
        $bossRole->givePermissionTo('config', 'ver-nomenclador', 'ver-gestion-guardia', 'ver-gestion-ambulatorio', 'facturar-guardia', 'facturar-ambulatorio-baja', 'facturar-ambulatorio-alta');

        $facturistaAmbBajaRole = Role::create(['name' => 'facturista-ambulatorio-baja']);
        $facturistaAmbBajaRole->givePermissionTo('ver-nomenclador', 'facturar-ambulatorio-baja');

        $facturistaAmbAltaRole = Role::create(['name' => 'facturista-ambulatorio-alta']);
        $facturistaAmbAltaRole->givePermissionTo('ver-nomenclador', 'facturar-ambulatorio-alta');

        $facturistaGuaRole = Role::create(['name' => 'facturista-guardia']);
        $facturistaGuaRole->givePermissionTo('ver-nomenclador', 'facturar-guardia');
    }
}
