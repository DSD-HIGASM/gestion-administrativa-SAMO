<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Configuracion extends Model
{
    use HasUlids;

    protected $table = 'configuraciones';
    protected $primaryKey = 'ulid';

    protected $fillable = [
        'ulid',
        'clave',
        'valor',
        'descripcion',
        'tipo_dato'
    ];
}
