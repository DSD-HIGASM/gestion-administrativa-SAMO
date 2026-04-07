<?php

namespace App\Imports;

use App\Models\AtencionGuardia;
use App\Models\HsiEspecialidad;
use App\Models\ArchivoImportado;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class AtencionesGuardiaImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private $archivoImportado;
    private $especialidadesCacheadas = [];

    public function __construct(ArchivoImportado $archivoImportado)
    {
        $this->archivoImportado = $archivoImportado;
    }

    // Le indicamos a Maatwebsite que la cabecera real está en la fila 5
    public function headingRow(): int
    {
        return 5;
    }

    public function collection(Collection $rows)
    {
        // Si es el primer chunk, contamos el total estimado para la barra de progreso
        if (empty($this->archivoImportado->total_filas)) {
            // Se calcula un estimado multiplicando el tamaño del chunk o leyendo las keys.
            // Para simplificar, le decimos a la BD que empezamos a procesar.
            $this->archivoImportado->update(['total_filas' => 9999]); // Placeholder hasta contar total real
        }

        foreach ($rows as $row) {
            // Ignorar filas vacías al final del excel
            if (!isset($row['id_episodio'])) {
                continue;
            }

            $nombreServicioCrud = trim($row['servicio'] ?? 'Sin Especificar');

            // 1. Normalización de especialidades (Caché en memoria)
            if (!isset($this->especialidadesCacheadas[$nombreServicioCrud])) {
                $especialidad = HsiEspecialidad::firstOrCreate(
                    ['nombre_crudo_hsi' => $nombreServicioCrud],
                    ['ulid' => strtolower((string) Str::ulid())]
                );
                $this->especialidadesCacheadas[$nombreServicioCrud] = $especialidad->ulid;
            }

            // 2. Insertar atención
            // Asumiendo que ya tienes el modelo y migración de AtencionGuardia
            AtencionGuardia::updateOrCreate(
                ['id_episodio_hsi' => $row['id_episodio']],
                [
                    'hsi_especialidad_ulid' => $this->especialidadesCacheadas[$nombreServicioCrud],
                    'fecha_atencion' => $row['fecha_y_hora_de_atencion'] ?? null,
                    // Agrega aquí las demás columnas que mapees a tu BD...
                ]
            );
        }

        // Sumamos las filas procesadas en este chunk a la base de datos
        $this->archivoImportado->increment('total_filas_procesadas', $rows->count());
    }

    // Procesar de a 500 filas para no saturar la RAM
    public function chunkSize(): int
    {
        return 500;
    }
}
