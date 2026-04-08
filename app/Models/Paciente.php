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
        'tipo_documento',
        'documento',
        'nombre_completo',
        'fecha_nacimiento',
        'genero_autopercibido',
        'telefono',
    ];
}
