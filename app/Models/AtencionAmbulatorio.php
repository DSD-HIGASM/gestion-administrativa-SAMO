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
        'ulid',
        'paciente_ulid',
        'archivo_ulid',
        'hash_atencion',
        'apellidos',
        'nombres',
        'tipo_documento',
        'numero_documento',
        'fecha_nacimiento',
        'telefono',
        'obra_social',
        'numero_afiliado',
        'fecha_atencion',
        'especialidad',
        'profesional',
        'problema',
        'practicas_estudios',
        'procedimientos'
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_atencion' => 'date',
        ];
    }
}
