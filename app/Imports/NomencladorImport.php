<?php

namespace App\Imports;

use App\Models\Nomenclador;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class NomencladorImport implements ToCollection, WithChunkReading
{
    protected $origen;

    public function __construct($origen)
    {
        $this->origen = $origen;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $codigo = null;
            $descripcion = null;
            $valor = null;
            $activo = true;

            // ==========================================
            // ESTRATEGIA 1: SAMO CRUDO
            // ==========================================
            if ($this->origen === 'SAMO' && isset($row[0], $row[1], $row[2]) && is_numeric(trim($row[0])) && is_numeric(trim($row[1])) && is_numeric(trim($row[2]))) {
                $codigo = trim($row[0]) . trim($row[1]) . trim($row[2]);
                $descripcion = trim($row[4] ?? (isset($row[3]) ? trim($row[3]) : ''));

                $valStr = preg_replace('/[^0-9\.,]/', '', trim($row[10] ?? (isset($row[11]) ? $row[11] : '')));
                if (is_numeric($valStr)) {
                    $valor = (float) $valStr;
                }
            }
            // ==========================================
            // ESTRATEGIA 2: PAMI CRUDO (CAZADOR AGRESIVO)
            // ==========================================
            elseif ($this->origen === 'PAMI') {
                // Buscamos en las primeras 3 columnas para atajar desplazamientos de celdas
                for ($i = 0; $i < 3; $i++) {
                    $celda = isset($row[$i]) ? trim($row[$i]) : '';

                    // Patrón A: Todo en una celda "861003 - SESION DE HEMODIAFILTRACION"
                    // (Acepta cualquier tipo de guion, multilínea, y espacios extra)
                    if (preg_match('/^(\d{4,8})\s*[-–—_]\s*(.+)/su', $celda, $matches)) {
                        $codigo = trim($matches[1]);
                        $descripcion = trim($matches[2]);
                        break;
                    }
                    // Patrón B: Código en Columna A ("861003") y Descripción en Columna B
                    elseif (preg_match('/^(\d{4,8})$/', $celda, $matches) && isset($row[$i+1]) && strlen(trim($row[$i+1])) > 3) {
                        $codigo = trim($matches[1]);
                        $descripcion = trim($row[$i+1]);
                        break;
                    }
                }
                $valor = null; // PAMI se carga sin precio por defecto
            }
            // ==========================================
            // ESTRATEGIA 3: PLANTILLA LIMPIA (O Custom)
            // ==========================================
            elseif (isset($row[0], $row[1]) && !preg_match('/^codigo$/i', trim($row[0]))) {
                if (is_numeric(trim($row[0])) || preg_match('/^[A-Z0-9\-]+$/i', trim($row[0]))) {
                    $codigo = trim($row[0]);
                    $descripcion = trim($row[1]);

                    $valStr = trim($row[2] ?? '');
                    if (is_numeric($valStr)) {
                        $valor = (float) $valStr;
                    }

                    $actStr = strtolower(trim($row[3] ?? 'si'));
                    $activo = ($actStr === 'no' || $actStr === '0' || $actStr === 'false') ? false : true;
                }
            }

            // Si no logramos rescatar un código y descripción, salteamos la fila (Basura del PDF)
            if (empty($codigo) || empty($descripcion)) {
                continue;
            }

            // Limpiamos saltos de línea molestos y espacios dobles en la descripción
            $descripcion = substr(trim(preg_replace('/\s+/', ' ', $descripcion)), 0, 1000);

            // Guardamos en Base de Datos
            Nomenclador::updateOrCreate(
                [
                    'codigo' => (string) $codigo,
                    'origen' => $this->origen
                ],
                [
                    'descripcion' => $descripcion,
                    'valor' => $valor,
                    'activo' => $activo
                ]
            );
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
