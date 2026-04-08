<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SamoAuditoria extends Model
{
    use HasFactory;
    protected $table = 'samo_auditorias';
    // No usa ULID, usa el ID autoincremental nativo
    protected $fillable = ['samo_tramite_ulid', 'usuario_ulid', 'accion', 'detalles'];

    protected function casts(): array {
        return ['detalles' => 'json'];
    }
}
