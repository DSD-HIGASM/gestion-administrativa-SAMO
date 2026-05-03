<?php

namespace Database\Seeders;

use App\Models\Cie10Catalogo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Cie10Seeder extends Seeder
{
    public function run(): void
    {
        // [Inferencia] Se lee el archivo desde la ruta configurada en el paso 1
        $json = File::get(database_path('data/cie10Data.json'));
        $data = json_decode($json, true);

        $records = [];
        $now = now();

        // [Inferencia] Se recorre la estructura de 4 niveles del JSON
        foreach ($data as $capitulo) {
            if (!isset($capitulo['subgrupos'])) continue;

            foreach ($capitulo['subgrupos'] as $subgrupo) {
                if (!isset($subgrupo['categorias'])) continue;

                foreach ($subgrupo['categorias'] as $catCode => $categoria) {

                    // [Especulación] Algunos códigos como "U88.X" no tienen sub-diagnósticos.
                    // En ese caso, guardamos la categoría principal como el diagnóstico a seleccionar.
                    if (empty($categoria['diagnosticos'])) {
                        $records[] = [
                            'ulid' => (string) Str::ulid(),
                            'codigo' => $catCode,
                            'descripcion' => $categoria['description'] ?? 'Sin descripción',
                            'activo' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    } else {
                        // Se extraen los códigos específicos
                        foreach ($categoria['diagnosticos'] as $diag) {
                            $records[] = [
                                'ulid' => (string) Str::ulid(),
                                'codigo' => $diag['code'],
                                'descripcion' => $diag['description'],
                                'activo' => true,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                }
            }
        }

        // [Inferencia] Se insertan en lotes de 1000 filas para evitar desbordamiento de memoria RAM
        foreach (array_chunk($records, 1000) as $chunk) {
            Cie10Catalogo::insert($chunk);
        }
    }
}
