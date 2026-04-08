<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class SamoTramiteDiagnostico extends Model
{
    use HasFactory, HasUlids, SoftDeletes;
    protected $table = 'samo_tramite_diagnosticos';
    protected $primaryKey = 'ulid';
    protected $fillable = ['ulid', 'samo_tramite_ulid', 'cie10_codigo', 'tipo_diagnostico'];
}
