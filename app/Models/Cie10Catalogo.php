<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Cie10Catalogo extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'cie10_catalogos'; // Apuntamos a tu tabla exacta
    protected $primaryKey = 'ulid';

    protected $fillable = [
        'ulid',
        'codigo',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
