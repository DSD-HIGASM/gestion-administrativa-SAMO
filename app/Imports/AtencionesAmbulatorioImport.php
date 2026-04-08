<?php

namespace App\Imports;

use App\Models\AtencionAmbulatorio;
use App\Models\HsiEspecialidad;
use App\Models\ArchivoImportado;
use App\Models\Paciente; // [Inferencia] Requerido para enlazar el paciente
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class AtencionesAmbulatorioImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private $archivoImportado;
    private $especialidadesCacheadas = [];
    private $pacientesCacheados = []; // Para no hacer cientos de queries de pacientes

    public function __construct(ArchivoImportado $archivoImportado)
    {
        $this->archivoImportado = $archivoImportado;
    }

    public function headingRow(): int
    {
        return 5;
    }

    public function collection(Collection $rows)
    {
        if (empty($this->archivoImportado->total_filas)) {
            $this->archivoImportado->update(['total_filas' => 9999]);
        }

        foreach ($rows as $row) {
            $dni = trim($row['nro_documento'] ?? '');
            $fechaAtencionCruda = trim($row['fecha_de_atencion'] ?? '');

            if (empty($dni) || empty($fechaAtencionCruda)) {
                continue;
            }

            // --- 1. Normalización de Fecha ---
            try {
                $fechaAtencion = Carbon::createFromFormat('d/m/Y', $fechaAtencionCruda)->format('Y-m-d');
            } catch (\Exception $e) {
                // Si la fecha viene rota en el Excel, la dejamos nula para que la base de datos no arroje error
                $fechaAtencion = null;
            }

            // --- 2. Normalización de Paciente ---
            if (!isset($this->pacientesCacheados[$dni])) {

                // Concatenamos el apellido y el nombre
                $apellido = trim($row['apellidos_paciente'] ?? '');
                $nombre = trim($row['nombres_paciente'] ?? '');
                $nombreCompleto = trim($apellido . ', ' . $nombre, ', '); // Quita comas extra si falta uno

                // [No verificado] Mapeamos las cabeceras exactas que vimos en tu CSV
                $paciente = Paciente::firstOrCreate(
                    ['documento' => $dni], // Busca por DNI
                    [
                        'ulid' => strtolower((string) Str::ulid()),
                        'tipo_documento' => trim($row['tipo_documento'] ?? 'DNI'),
                        'nombre_completo' => $nombreCompleto ?: 'Sin Especificar',
                        'fecha_nacimiento' => trim($row['fecha_de_nacimiento'] ?? null),
                        'genero_autopercibido' => trim($row['genero_autopercibido'] ?? null),
                        'telefono' => trim($row['telefono'] ?? null),
                    ]
                );
                $this->pacientesCacheados[$dni] = $paciente->ulid;
            }
            $pacienteUlid = $this->pacientesCacheados[$dni];

            // --- 3. Normalización de Especialidad ---
            $nombreServicioCrud = trim($row['especialidad'] ?? 'Sin Especificar');

            if (!isset($this->especialidadesCacheadas[$nombreServicioCrud])) {
                $especialidad = HsiEspecialidad::firstOrCreate(
                    ['nombre_crudo_hsi' => $nombreServicioCrud],
                    ['ulid' => strtolower((string) Str::ulid())]
                );
                $this->especialidadesCacheadas[$nombreServicioCrud] = $especialidad->ulid;
            }
            $especialidadUlid = $this->especialidadesCacheadas[$nombreServicioCrud];

            // --- 4. Inserción de la Atención ---
            $hashString = $dni . '|' . $fechaAtencionCruda . '|' . $nombreServicioCrud;
            $hashAtencion = hash('sha256', $hashString);

            // Guardamos usando la estructura exacta de tu migración
            AtencionAmbulatorio::updateOrCreate(
                ['hash_atencion' => $hashAtencion],
                [
                    'paciente_ulid' => $pacienteUlid,
                    'archivo_ulid' => $this->archivoImportado->ulid, // Enlazamos la atención con el Excel subido
                    'hsi_especialidad_ulid' => $especialidadUlid,
                    'fecha_atencion' => $fechaAtencion,
                    'profesional' => $row['profesional'] ?? null,

                    // [Inferencia] Maatwebsite suele quitar barras y espacios en las cabeceras.
                    'obra_social_paciente' => $row['obra_socialprepaga'] ?? null,
                    'numero_afiliado' => $row['nro_de_afiliado'] ?? null,
                    'problemas_salud_diagnostico' => $row['problemas_de_salud_diagnostico'] ?? null,
                    'practicas_estudios' => $row['practicasestudios'] ?? null,
                ]
            );
        }

        $this->archivoImportado->increment('total_filas_procesadas', $rows->count());
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
