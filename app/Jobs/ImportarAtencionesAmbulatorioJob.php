<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\ArchivoImportado;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AtencionesAmbulatorioImport;
use Exception;

class ImportarAtencionesAmbulatorioJob implements ShouldQueue
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

            Excel::import(
                new AtencionesAmbulatorioImport($this->archivoImportado),
                $this->archivoImportado->ruta_servidor,
                'local'
            );

            $this->archivoImportado->update(['estado_procesamiento' => 'completado']);

        } catch (Exception $e) {
            $this->archivoImportado->update([
                'estado_procesamiento' => 'con_errores',
                'errores' => json_encode(['mensaje' => $e->getMessage()])
            ]);
        }
    }
}
