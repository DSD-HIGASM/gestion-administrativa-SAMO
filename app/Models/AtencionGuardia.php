<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class AtencionGuardia extends Model
{
    use HasFactory, HasUlids;

    // 1. Forzar el nombre de la tabla
    protected $table = 'atenciones_guardia';

    // 2. Definir la clave primaria
    protected $primaryKey = 'ulid';

    // 3. Habilitar la asignación masiva
    protected $fillable = [
        'ulid',
        'id_episodio_hsi',
        'hsi_especialidad_ulid',
        'fecha_atencion',
        // [Inferencia] Agrega aquí cualquier otra columna que tengas en tu migración
    ];
}
