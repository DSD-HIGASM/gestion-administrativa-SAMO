<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class SamoEstado extends Model
{
    use HasFactory, HasUlids;
    protected $table = 'samo_estados';
    protected $primaryKey = 'ulid';
    protected $fillable = ['ulid', 'nombre', 'color_hex', 'es_estado_inicial', 'es_estado_final', 'orden_logico'];

    protected function casts(): array {
        return ['es_estado_inicial' => 'boolean', 'es_estado_final' => 'boolean'];
    }
}
