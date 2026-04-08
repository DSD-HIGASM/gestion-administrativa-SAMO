<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class AtencionAmbulatorio extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'atenciones_ambulatorio';
    protected $primaryKey = 'ulid';

    protected $fillable = [
        'paciente_ulid',
        'archivo_ulid',
        'hsi_especialidad_ulid',
        'nomenclador_ulid',
        'hash_atencion',
        'fecha_atencion',
        'profesional',
        'obra_social_paciente',
        'numero_afiliado',
        'problemas_salud_diagnostico',
        'practicas_estudios',
        'codigo_cie10_asignado',
        'estado_samo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_atencion' => 'date',
        ];
    }
}
