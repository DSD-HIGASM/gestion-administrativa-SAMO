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

    // Aquí está la magia: permitimos que todos estos campos se guarden
    protected $fillable = [
        'ulid',
        'samo_tramite_ulid',
        'paciente_ulid',
        'nombre_visible',
        'nombre_original',
        'ruta_archivo',
        'extension',
        'peso_bytes',
        'subido_por_usuario_ulid'
    ];

    // Relaciones clave
    public function tramite()
    {
        return $this->belongsTo(SamoTramite::class, 'samo_tramite_ulid', 'ulid');
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_ulid', 'ulid');
    }

    public function subidoPorUsuario()
    {
        return $this->belongsTo(User::class, 'subido_por_usuario_ulid', 'ulid');
    }
}
