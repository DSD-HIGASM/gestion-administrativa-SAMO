<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Paciente extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'pacientes';
    protected $primaryKey = 'ulid';

    protected $fillable = [
        'ulid',
        'apellidos',
        'nombres',
        'tipo_documento',
        'documento',
        'fecha_nacimiento',
        'telefono',
        'obras_sociales',
        'id_paciente_hsi',
        'sexo',
        'samo_coberturas',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'obras_sociales' => 'array',
            'samo_coberturas' => 'array',
        ];
    }
}
