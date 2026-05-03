<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Exclusion extends Model
{
    use HasUlids;

    protected $table = 'exclusiones';
    protected $primaryKey = 'ulid';

    protected $fillable = [
        'ulid',
        'tipo_exclusion',
        'valor_exacto',
        'motivo_exclusion',
        'activa'
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }
}
