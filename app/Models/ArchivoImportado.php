<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class ArchivoImportado extends Model
{
    use HasFactory, HasUlids;

    // 1. Forzamos el nombre exacto de la tabla
    protected $table = 'archivos_importados';

    // 2. Le indicamos que la clave primaria es el ULID
    protected $primaryKey = 'ulid';

    // 3. Habilitamos la asignación masiva para todos los campos
    protected $fillable = [
        'nombre_original',
        'ruta_servidor',
        'origen_datos',
        'estado_procesamiento',
        'batch_id',
        'total_filas',
        'total_filas_procesadas',
        'errores',
        'subido_por_usuario_ulid',
    ];

    // Casteos opcionales pero recomendados para el JSON y enteros
    protected function casts(): array
    {
        return [
            'errores' => 'array',
            'total_filas' => 'integer',
            'total_filas_procesadas' => 'integer',
        ];
    }

    // 4. Relación con el usuario que subió el archivo
    public function usuario()
    {
        return $this->belongsTo(User::class, 'subido_por_usuario_ulid', 'ulid');
    }
}
