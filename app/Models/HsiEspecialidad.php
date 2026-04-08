<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HsiEspecialidad extends Model
{
    use HasUlids, HasFactory;

    protected $table = 'hsi_especialidades';
    protected $primaryKey = 'ulid';

    protected $fillable = [
        'nombre_crudo_hsi',
        'service_ulid',
    ];

    // Relación hacia tu catálogo normalizado
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_ulid', 'ulid');
    }
}
