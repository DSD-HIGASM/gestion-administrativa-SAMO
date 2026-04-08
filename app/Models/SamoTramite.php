<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class SamoTramite extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'samo_tramites';
    protected $primaryKey = 'ulid';

    protected $fillable = [
        'ulid', 'codigo_visual', 'paciente_ulid', 'atencion_guardia_ulid', 'atencion_ambulatorio_ulid',
        'estado_ulid', 'asignado_a_usuario_ulid', 'obra_social_facturada', 'numero_afiliado_facturado',
        'numero_bono_autorizacion', 'monto_total_calculado', 'fecha_vencimiento_presentacion', 'observaciones_internas'
    ];

    protected function casts(): array
    {
        return [
            'fecha_vencimiento_presentacion' => 'date',
            'monto_total_calculado' => 'decimal:2',
        ];
    }

    // --- LA VÁLVULA DE SEGURIDAD (Generador de Código Visual Autónomo) ---
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tramite) {
            if (empty($tramite->codigo_visual)) {
                $tramite->codigo_visual = self::generarCodigoVisualUnico();
            }
        });
    }

    public static function generarCodigoVisualUnico()
    {
        // 31 caracteres limpios (sin O, 0, I, 1, L) para evitar confusiones humanas
        $caracteres = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $longitud = 6;
        $maxIntentos = 10;

        while (true) {
            for ($intentos = 0; $intentos < $maxIntentos; $intentos++) {
                $codigo = substr(str_shuffle(str_repeat($caracteres, ceil($longitud / strlen($caracteres)))), 1, $longitud);
                if (!self::where('codigo_visual', $codigo)->exists()) {
                    return $codigo; // Código limpio encontrado
                }
            }
            $longitud++; // Si la base colapsó 10 veces seguidas, expandimos el universo
        }
    }
}
