<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SamoEstado;
use Illuminate\Support\Str;

class SamoEstadoSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            // El estado_inicial = true es CLAVE porque es el que buscarán los importadores
            ['nombre' => 'Pendiente de Carga', 'color_hex' => '#FBBF24', 'es_estado_inicial' => true, 'es_estado_final' => false, 'orden_logico' => 1],
            ['nombre' => 'En Auditoría', 'color_hex' => '#60A5FA', 'es_estado_inicial' => false, 'es_estado_final' => false, 'orden_logico' => 2],
            ['nombre' => 'Listo para Facturar', 'color_hex' => '#34D399', 'es_estado_inicial' => false, 'es_estado_final' => false, 'orden_logico' => 3],
            ['nombre' => 'Enviado a OS', 'color_hex' => '#818CF8', 'es_estado_inicial' => false, 'es_estado_final' => false, 'orden_logico' => 4],
            ['nombre' => 'Facturado / Cobrado', 'color_hex' => '#10B981', 'es_estado_inicial' => false, 'es_estado_final' => true, 'orden_logico' => 5],
            ['nombre' => 'Rechazado / Débito', 'color_hex' => '#EF4444', 'es_estado_inicial' => false, 'es_estado_final' => true, 'orden_logico' => 6],
        ];

        foreach ($estados as $estado) {
            SamoEstado::updateOrCreate(
                ['nombre' => $estado['nombre']], // Evita duplicados si se corre varias veces
                array_merge(['ulid' => strtolower((string) Str::ulid())], $estado)
            );
        }
    }
}
