<?php

namespace App\Imports;

use App\Models\AtencionAmbulatorio;
use App\Models\ArchivoImportado;
use App\Models\Paciente;
use App\Models\SamoTramite;
use App\Models\SamoEstado;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class AtencionesAmbulatorioImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private $archivoImportado;
    private $pacientesCacheados = [];
    private $estadoInicialUlid;

    public function __construct(ArchivoImportado $archivoImportado)
    {
        $this->archivoImportado = $archivoImportado;

        // Buscamos el estado inicial una sola vez al instanciar la clase
        $estadoInicial = SamoEstado::where('es_estado_inicial', true)->first();
        $this->estadoInicialUlid = $estadoInicial ? $estadoInicial->ulid : null;
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

            // Normalización de Fechas
            $fechaAtencion = null;
            $fechaNacimiento = null;
            try {
                if (!empty($fechaAtencionCruda)) $fechaAtencion = Carbon::createFromFormat('d/m/Y', $fechaAtencionCruda)->format('Y-m-d');
                if (!empty($row['fecha_de_nacimiento'])) $fechaNacimiento = Carbon::createFromFormat('d/m/Y', trim($row['fecha_de_nacimiento']))->format('Y-m-d');
            } catch (\Exception $e) {}

            $apellido = trim($row['apellidos_paciente'] ?? '');
            $nombre = trim($row['nombres_paciente'] ?? '');

            // Forzamos nulos en campos numéricos vacíos
            $telefono = trim($row['telefono'] ?? '') === '' ? null : trim($row['telefono']);

            // 1. Actualizar/Crear Paciente Central
            if (!isset($this->pacientesCacheados[$dni])) {
                $paciente = Paciente::updateOrCreate(
                    ['documento' => $dni],
                    [
                        'apellidos' => $apellido ?: 'Sin Especificar',
                        'nombres' => $nombre ?: 'Sin Especificar',
                        'tipo_documento' => trim($row['tipo_documento'] ?? 'DNI'),
                        'fecha_nacimiento' => $fechaNacimiento,
                        'telefono' => $telefono,
                    ]
                );
                $this->pacientesCacheados[$dni] = $paciente->ulid;
            }
            $pacienteUlid = $this->pacientesCacheados[$dni];

            // 2. Hash
            $hashString = $dni . '|' . $fechaAtencionCruda . '|' . trim($row['especialidad'] ?? '');
            $hashAtencion = hash('sha256', $hashString);

            // 3. Guardar Atención Ambulatorio
            $atencion = AtencionAmbulatorio::updateOrCreate(
                ['hash_atencion' => $hashAtencion],
                [
                    'paciente_ulid' => $pacienteUlid,
                    'archivo_ulid' => $this->archivoImportado->ulid,
                    'apellidos' => $apellido,
                    'nombres' => $nombre,
                    'tipo_documento' => trim($row['tipo_documento'] ?? 'DNI'),
                    'numero_documento' => $dni,
                    'fecha_nacimiento' => $fechaNacimiento,
                    'telefono' => $telefono,
                    'obra_social' => trim($row['obra_socialprepaga'] ?? null),
                    'numero_afiliado' => trim($row['nro_de_afiliado'] ?? null),
                    'fecha_atencion' => $fechaAtencion,
                    'especialidad' => trim($row['especialidad'] ?? 'Sin Especificar'),
                    'profesional' => trim($row['profesional'] ?? ''),
                    'problema' => trim($row['problemas_de_salud_diagnostico'] ?? null),
                    'practicas_estudios' => trim($row['practicasestudios'] ?? null),
                    'procedimientos' => trim($row['procedimientos'] ?? null),
                ]
            );

            // 4. Generar el Expediente de Facturación SAMO
            if ($this->estadoInicialUlid) {
                SamoTramite::firstOrCreate(
                    ['atencion_ambulatorio_ulid' => $atencion->ulid],
                    [
                        'paciente_ulid' => $pacienteUlid,
                        'estado_ulid' => $this->estadoInicialUlid,
                        'obra_social_facturada' => trim($row['obra_socialprepaga'] ?? null),
                        'numero_afiliado_facturado' => trim($row['nro_de_afiliado'] ?? null),
                    ]
                );
            }
        }

        $this->archivoImportado->increment('total_filas_procesadas', $rows->count());
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
