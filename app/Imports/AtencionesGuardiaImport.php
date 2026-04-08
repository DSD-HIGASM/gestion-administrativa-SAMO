<?php

namespace App\Imports;

use App\Models\AtencionGuardia;
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

class AtencionesGuardiaImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private $archivoImportado;
    private $pacientesCacheados = [];
    private $estadoInicialUlid;

    public function __construct(ArchivoImportado $archivoImportado)
    {
        $this->archivoImportado = $archivoImportado;

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
            $idEpisodio = trim($row['id_episodio'] ?? '');
            if (empty($idEpisodio)) {
                continue;
            }

            $dni = trim($row['nro_documento'] ?? '');
            $apellido = trim($row['apellidos_paciente'] ?? '');
            $nombre = trim($row['nombres_paciente'] ?? '');

            $fechaNacimiento = !empty($row['fecha_de_nacimiento']) ? Carbon::createFromFormat('d/m/Y', trim($row['fecha_de_nacimiento']))->format('Y-m-d') : null;
            $fechaHoraApertura = !empty($row['hora_de_apertura_del_episodio']) ? Carbon::parse(trim($row['hora_de_apertura_del_episodio']))->format('Y-m-d H:i:s') : null;
            $fechaHoraAtencion = !empty($row['fecha_y_hora_de_atencion']) ? Carbon::parse(trim($row['fecha_y_hora_de_atencion']))->format('Y-m-d H:i:s') : null;
            $fechaHoraAlta = !empty($row['fecha_y_hora_alta_medica']) ? Carbon::parse(trim($row['fecha_y_hora_alta_medica']))->format('Y-m-d H:i:s') : null;

            // Forzamos nulos en campos numéricos vacíos
            $telefono = trim($row['telefono'] ?? '') === '' ? null : trim($row['telefono']);
            $idPacienteHsi = trim($row['id_paciente_hsi'] ?? '') === '' ? null : trim($row['id_paciente_hsi']);
            $cantidadTriage = trim($row['cantidad_triages_episodio'] ?? '') === '' ? null : trim($row['cantidad_triages_episodio']);

            // 1. Actualizar/Crear Paciente Central
            $pacienteUlid = null;
            if (!empty($dni)) {
                if (!isset($this->pacientesCacheados[$dni])) {
                    $paciente = Paciente::updateOrCreate(
                        ['documento' => $dni],
                        [
                            'apellidos' => $apellido ?: 'Sin Especificar',
                            'nombres' => $nombre ?: 'Sin Especificar',
                            'tipo_documento' => trim($row['tipo_documento'] ?? 'DNI'),
                            'fecha_nacimiento' => $fechaNacimiento,
                            'telefono' => $telefono,
                            'id_paciente_hsi' => $idPacienteHsi,
                            'sexo' => trim($row['sexo_documento'] ?? null),
                        ]
                    );
                    $this->pacientesCacheados[$dni] = $paciente->ulid;
                }
                $pacienteUlid = $this->pacientesCacheados[$dni];
            }

            // 2. Guardar Atención Guardia
            $atencion = AtencionGuardia::updateOrCreate(
                ['id_episodio' => $idEpisodio],
                [
                    'paciente_ulid' => $pacienteUlid,
                    'archivo_ulid' => $this->archivoImportado->ulid,
                    'id_paciente_hsi' => $idPacienteHsi,
                    'apellidos' => $apellido,
                    'nombres' => $nombre,
                    'tipo_documento' => trim($row['tipo_documento'] ?? 'DNI'),
                    'numero_documento' => $dni,
                    'fecha_nacimiento' => $fechaNacimiento,
                    'sexo' => trim($row['sexo_documento'] ?? null),
                    'telefono' => $telefono,
                    'obra_social' => trim($row['obra_socialprepaga_asociada_episodio'] ?? null),
                    'numero_afiliado' => trim($row['nro_de_afiliado'] ?? null),

                    'fecha_hora_apertura' => $fechaHoraApertura,
                    'cantidad_triage' => $cantidadTriage,
                    'servicio' => trim($row['servicio'] ?? 'Sin Especificar'),
                    'fecha_hora_atencion' => $fechaHoraAtencion,
                    'diagnostico' => trim($row['diagnosticos'] ?? null),
                    'fecha_hora_alta' => $fechaHoraAlta,
                    'practicas_procedimientos' => trim($row['practicaprocedimientos'] ?? null),
                    'profesional' => trim($row['profesional_consulta'] ?? null),
                    'profesional_alta' => trim($row['profesional_alta_medica'] ?? null),
                    'tipo_egreso' => trim($row['tipo_egreso'] ?? null),
                ]
            );

            // 3. Generar el Expediente de Facturación SAMO
            if ($this->estadoInicialUlid) {
                SamoTramite::firstOrCreate(
                    ['atencion_guardia_ulid' => $atencion->ulid],
                    [
                        'paciente_ulid' => $pacienteUlid,
                        'estado_ulid' => $this->estadoInicialUlid,
                        'obra_social_facturada' => trim($row['obra_socialprepaga_asociada_episodio'] ?? null),
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
