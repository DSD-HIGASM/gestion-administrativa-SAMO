<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ArchivoImportado;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

// [Inferencia] Deberás crear estos Jobs y referenciarlos aquí
use App\Jobs\ImportarAtencionesGuardiaJob;
use App\Jobs\ImportarAtencionesAmbulatorioJob;

new class extends Component
{
    use WithFileUploads;

    public $archivoExcel;
    public $origen = '';
    public $mostrarModalSubida = false;

    public function abrirModal()
    {
        $this->reset(['archivoExcel', 'origen']);
        $this->resetValidation();
        $this->mostrarModalSubida = true;
    }

    public function cerrarModal()
    {
        $this->mostrarModalSubida = false;
        // Limpiamos el archivo temporal al cerrar
        $this->reset('archivoExcel');
    }

    public function procesarArchivo()
    {
        $this->validate([
            'origen' => 'required|in:guardia,ambulatorio',
            // max:51200 equivale a 50MB
            'archivoExcel' => 'required|file|mimes:xls|max:51200',
        ]);

        $extension = $this->archivoExcel->getClientOriginalExtension();
        $nombreOriginal = $this->archivoExcel->getClientOriginalName();
        $timestamp = now()->format('Ymd_His');
        $mes = now()->format('m');
        $anio = now()->format('Y');

        $path = "private/imports/{$anio}/{$mes}";
        $filename = "{$this->origen}_{$timestamp}.{$extension}";

        // Almacenamiento local privado
        $rutaGuardada = $this->archivoExcel->storeAs($path, $filename, 'local');

        $archivoImportado = ArchivoImportado::create([
            'ulid' => strtolower((string) Str::ulid()),
            'nombre_original' => $nombreOriginal,
            'ruta_servidor' => $rutaGuardada,
            'origen_datos' => $this->origen,
            'estado_procesamiento' => 'pendiente',
            'subido_por_usuario_ulid' => auth()->user()->ulid,
        ]);

        // Disparamos el Job correspondiente a la cola
        if ($this->origen === 'guardia') {
            ImportarAtencionesGuardiaJob::dispatch($archivoImportado);
        } elseif ($this->origen === 'ambulatorio') {
            ImportarAtencionesAmbulatorioJob::dispatch($archivoImportado);
        }

        $this->cerrarModal();
        session()->flash('success', 'El archivo se encoló correctamente y está siendo procesado en segundo plano.');
    }

    public function descargarArchivo($ulid)
    {
        $archivo = ArchivoImportado::findOrFail($ulid);

        if (Storage::disk('local')->exists($archivo->ruta_servidor)) {
            return Storage::disk('local')->download($archivo->ruta_servidor, $archivo->nombre_original);
        }

        session()->flash('error', 'El archivo físico ya no se encuentra en el servidor.');
    }

    public function with(): array
    {
        $lotes = ArchivoImportado::with('usuario')->latest()->get()->map(function ($lote) {
            // Calcular porcentaje simple basado en las filas procesadas
            if ($lote->estado_procesamiento === 'completado') {
                $lote->progreso_calculado = 100;
            } elseif ($lote->estado_procesamiento === 'procesando') {
                // Cálculo de progreso visual falso o real dependiendo de si tenemos total_filas
                $lote->progreso_calculado = $lote->total_filas_procesadas > 0 ? 50 : 10;
            } else {
                $lote->progreso_calculado = 0;
            }
            return $lote;
        });

        return [
            'historialLotes' => $lotes
        ];
    }
};
?>

<div>
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden" wire:poll.3s>

        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white flex items-center justify-between">
            <div>
                <h3 class="font-pba font-extrabold text-xl text-pba-blue tracking-tight">Historial de Lotes Procesados</h3>
                <p class="font-sans text-xs text-gray-500 mt-1">Monitoreo en tiempo real de la ingesta de datos HSI.</p>
            </div>

            <button wire:click="abrirModal" class="px-5 py-2.5 bg-pba-cyan text-white rounded-xl text-sm font-pba font-bold shadow-md hover:bg-opacity-90 hover:shadow-lg transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Importar Excel
            </button>
        </div>

        @if(session()->has('success'))
            <div class="bg-green-50 border-l-4 border-green-500 p-4 m-6 rounded-r-lg">
                <div class="flex">
                    <div class="flex-shrink-0"><svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg></div>
                    <div class="ml-3"><p class="text-sm font-sans font-bold text-green-800">{{ session('success') }}</p></div>
                </div>
            </div>
        @endif

        @if(session()->has('error'))
            <div class="bg-red-50 border-l-4 border-red-500 p-4 m-6 rounded-r-lg">
                <div class="flex">
                    <div class="flex-shrink-0"><svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg></div>
                    <div class="ml-3"><p class="text-sm font-sans font-bold text-red-800">{{ session('error') }}</p></div>
                </div>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Fecha</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Operador</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Origen / Archivo</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Progreso</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400 text-center">Estado</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400 text-right">Acciones</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                @forelse ($historialLotes as $lote)
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="px-6 py-4 font-sans text-xs text-gray-600 font-medium">
                            {{ $lote->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 font-sans text-xs text-gray-800 font-bold">
                            {{ $lote->usuario->lastname ?? 'Sistema' }}, {{ $lote->usuario->name ?? '' }}
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-sans text-xs font-bold text-pba-blue uppercase">{{ $lote->origen_datos }}</p>
                            <p class="font-mono text-[10px] text-gray-400 mt-0.5 truncate max-w-[200px]" title="{{ $lote->nombre_original }}">{{ $lote->nombre_original }}</p>
                        </td>
                        <td class="px-6 py-4 w-48">
                            <div class="flex items-center gap-3">
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-pba-cyan h-1.5 rounded-full transition-all duration-500" style="width: {{ $lote->progreso }}%"></div>
                                </div>
                                <span class="font-sans text-[10px] font-bold text-gray-500">{{ $lote->progreso }}%</span>
                            </div>
                            <p class="font-sans text-[9px] text-gray-400 mt-1">Proc: {{ $lote->total_filas_procesadas }} / {{ $lote->total_filas ?? '?' }}</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($lote->estado_procesamiento === 'completado')
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-bold bg-green-50 text-green-700 border border-green-200 uppercase">Completado</span>
                            @elseif($lote->estado_procesamiento === 'procesando')
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 uppercase animate-pulse">
                                        <svg class="animate-spin h-3 w-3 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        Procesando
                                    </span>
                            @elseif($lote->estado_procesamiento === 'con_errores')
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-bold bg-red-50 text-red-700 border border-red-200 uppercase" title="Verifica los logs para más detalles">Con Errores</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200 uppercase">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="descargarArchivo('{{ $lote->ulid }}')" class="text-gray-400 hover:text-pba-cyan transition-colors p-1" title="Descargar Excel original">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center font-sans text-sm text-gray-400">No hay archivos importados en el historial.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($mostrarModalSubida)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" wire:click="cerrarModal"></div>
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-2xl bg-white shadow-2xl w-full max-w-lg">

                    <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                        <h3 class="font-pba font-bold text-lg text-pba-blue">Ingesta de Lote HSI</h3>
                        <button wire:click="cerrarModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <form wire:submit="procesarArchivo" class="p-6 space-y-6">

                        <div>
                            <label class="block font-sans text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">Origen de los datos</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="relative cursor-pointer">
                                    <input type="radio" wire:model="origen" value="guardia" class="peer sr-only">
                                    <div class="p-3 border-2 border-gray-100 rounded-xl text-center peer-checked:border-pba-cyan peer-checked:bg-pba-cyan/5 hover:bg-gray-50 transition-all">
                                        <p class="font-pba font-bold text-sm text-gray-700 peer-checked:text-pba-blue">Guardia</p>
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input type="radio" wire:model="origen" value="ambulatorio" class="peer sr-only">
                                    <div class="p-3 border-2 border-gray-100 rounded-xl text-center peer-checked:border-pba-cyan peer-checked:bg-pba-cyan/5 hover:bg-gray-50 transition-all">
                                        <p class="font-pba font-bold text-sm text-gray-700 peer-checked:text-pba-blue">Ambulatorio</p>
                                    </div>
                                </label>
                                <div class="p-3 border-2 border-gray-100 rounded-xl text-center bg-gray-50 opacity-50 cursor-not-allowed">
                                    <p class="font-pba font-bold text-sm text-gray-400">Imágenes</p>
                                </div>
                                <div class="p-3 border-2 border-gray-100 rounded-xl text-center bg-gray-50 opacity-50 cursor-not-allowed">
                                    <p class="font-pba font-bold text-sm text-gray-400">Internación</p>
                                </div>
                            </div>
                            @error('origen') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-sans text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">Archivo (.xls)</label>
                            <input
                                type="file"
                                wire:model="archivoExcel"
                                accept=".xls"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-pba-cyan/10 file:text-pba-cyan hover:file:bg-pba-cyan/20 transition-all cursor-pointer">

                            <div wire:loading wire:target="archivoExcel" class="text-[10px] text-pba-cyan mt-2 font-bold animate-pulse">
                                Cargando archivo al servidor temporal...
                            </div>
                            @error('archivoExcel') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end pt-4 border-t border-gray-100">
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                class="px-6 py-2.5 bg-pba-blue text-white rounded-xl text-sm font-pba font-bold shadow-md hover:bg-pba-cyan transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                                <span wire:loading.remove wire:target="procesarArchivo">Comenzar Ingesta</span>
                                <span wire:loading wire:target="procesarArchivo">Encolando Lote...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
