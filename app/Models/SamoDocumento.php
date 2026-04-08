<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class SamoDocumento extends Model
{
    use HasFactory, HasUlids, SoftDeletes;
    protected $table = 'samo_documentos';
    protected $primaryKey = 'ulid';
    protected $fillable = ['ulid', 'paciente_ulid', 'samo_tramite_ulid', 'categoria', 'ruta_archivo', 'nombre_original'];
}
