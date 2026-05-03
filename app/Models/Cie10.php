<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Cie10 extends Model
{
    use HasUlids; // Habilitamos los ULIDs para esta tabla

    protected $table = 'cie10_catalogos'; // Apuntamos a la tabla correcta
    protected $primaryKey = 'ulid'; // Le decimos que la primary key es el ULID

    protected $fillable = ['ulid', 'codigo', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
