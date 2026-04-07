<?php

namespace App\Imports;

use App\Models\AtencionAmbulatorio; // [Inferencia] Asumo que el modelo se llama así
use App\Models\HsiEspecialidad;
use App\Models\ArchivoImportado;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class AtencionesAmbulatorioImport implements ToCollection, WithHeadingRow, WithChunkReading
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
        // Si es el primer chunk, fijamos un total estimado para que la barra no quede en 0
        if (empty($this->archivoImportado->total_filas)) {
            $this->archivoImportado->update(['total_filas' => 9999]);
        }

        foreach ($rows as $row) {
            // [No verificado] Ignorar filas vacías al final del excel validando que exista documento y fecha
            if (empty($row['nro_documento']) || empty($row['fecha_de_atencion'])) {
                continue;
            }

            // Maatwebsite convierte las cabeceras a snake_case automáticamente ('Especialidad' -> 'especialidad')
            $nombreServicioCrud = trim($row['especialidad'] ?? 'Sin Especificar');

            // 1. Normalización de especialidades (Caché en memoria para evitar colapso N+1)
            if (!isset($this->especialidadesCacheadas[$nombreServicioCrud])) {
                $especialidad = HsiEspecialidad::firstOrCreate(
                    ['nombre_crudo_hsi' => $nombreServicioCrud],
                    ['ulid' => strtolower((string) Str::ulid())]
                );
                $this->especialidadesCacheadas[$nombreServicioCrud] = $especialidad->ulid;
            }

            $especialidadUlid = $this->especialidadesCacheadas[$nombreServicioCrud];

            // 2. Generación del Hash Algorítmico para validación de duplicados
            $dni = trim($row['nro_documento']);
            $fechaAtencion = trim($row['fecha_de_atencion']);

            // Concatenamos DNI + Fecha + Especialidad separados por un pipe
            $hashString = $dni . '|' . $fechaAtencion . '|' . $nombreServicioCrud;
            $hashAtencion = hash('sha256', $hashString);

            // 3. Insertar o actualizar atención
            // [Especulación] Mapeo de columnas basado en las cabeceras del CSV.
            // Pide aclaración si los nombres de las columnas en tu base de datos son diferentes.
            AtencionAmbulatorio::updateOrCreate(
                ['hash_atencion' => $hashAtencion],
                [
                    'hsi_especialidad_ulid' => $especialidadUlid,
                    'nro_documento' => $dni,
                    'fecha_atencion' => $fechaAtencion,
                    'nombres_paciente' => $row['nombres_paciente'] ?? null,
                    'apellidos_paciente' => $row['apellidos_paciente'] ?? null,
                    'profesional' => $row['profesional'] ?? null,
                    'motivo_consulta' => $row['motivo_de_consulta'] ?? null,
                    'diagnostico' => $row['problemas_de_salud_diagnostico'] ?? null,
                    // Agrega aquí el resto de las columnas (peso, talla, etc.) según tu migración
                ]
            );
        }

        // Sumamos las filas procesadas en este chunk
        $this->archivoImportado->increment('total_filas_procesadas', $rows->count());
    }

    // Procesar de a 500 filas
    public function chunkSize(): int
    {
        return 500;
    }
}
