<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;


class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(SamoEstadoSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Cristian Jonathan',
            'lastname' => 'Lamas',
            'document' => '43255000',
            'password' => Hash::make('43255000'),
        ]);

        $admin->assignRole('dev');

    }
}
