<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class AtencionGuardia extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'atenciones_guardia';
    protected $primaryKey = 'ulid';

    protected $fillable = [
        'ulid',
        'paciente_ulid',
        'archivo_ulid',
        'id_paciente_hsi',
        'apellidos',
        'nombres',
        'tipo_documento',
        'numero_documento',
        'fecha_nacimiento',
        'sexo',
        'telefono',
        'obra_social',
        'numero_afiliado',
        'id_episodio',
        'fecha_hora_apertura',
        'cantidad_triage',
        'servicio',
        'fecha_hora_atencion',
        'diagnostico',
        'fecha_hora_alta',
        'practicas_procedimientos',
        'profesional',
        'profesional_alta',
        'tipo_egreso'
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_hora_apertura' => 'datetime',
            'fecha_hora_atencion' => 'datetime',
            'fecha_hora_alta' => 'datetime',
        ];
    }
}
