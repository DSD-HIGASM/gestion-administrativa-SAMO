<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\ArchivoImportado;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AtencionesGuardiaImport;
use Exception;

class ImportarAtencionesGuardiaJob implements ShouldQueue
{
    use Queueable;

    public $archivoImportado;

    public function __construct(ArchivoImportado $archivoImportado)
    {
        $this->archivoImportado = $archivoImportado;
    }

    public function handle(): void
    {
        try {
            $this->archivoImportado->update(['estado_procesamiento' => 'procesando']);

            // Ejecutamos la lectura del Excel
            Excel::import(
                new AtencionesGuardiaImport($this->archivoImportado),
                $this->archivoImportado->ruta_servidor,
                'local'
            );

            // Si llega hasta acá sin lanzar excepciones, terminó el 100%
            $this->archivoImportado->update([
                'estado_procesamiento' => 'completado',
                'progreso' => 100
            ]);

        } catch (Exception $e) {
            $this->archivoImportado->update([
                'estado_procesamiento' => 'con_errores',
                'errores' => json_encode([
                    'mensaje' => $e->getMessage(),
                    'linea' => $e->getLine(),
                    'archivo' => $e->getFile()
                ])
            ]);
        }
    }
}
