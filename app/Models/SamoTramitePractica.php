<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class SamoTramitePractica extends Model
{
    use HasFactory, HasUlids, SoftDeletes;
    protected $table = 'samo_tramite_practicas';
    protected $primaryKey = 'ulid';
    protected $fillable = [
        'ulid', 'samo_tramite_ulid', 'nomenclador_origen', 'codigo_practica',
        'descripcion_practica', 'cantidad', 'valor_unitario', 'valor_subtotal'
    ];
}
