<?php

use Livewire\Component;
use App\Models\SamoTramite;
use App\Models\SamoTramitePractica;
use App\Models\SamoTramiteDiagnostico;
use App\Models\SamoEstado;
use App\Models\SamoAuditoria;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public SamoTramite $tramite;

    public $practicas = [];
    public $diagnosticos = [];
    public $estadosDisponibles = [];
    public $auditorias = [];

    public $nuevaPractica = ['nomenclador_origen' => 'SAMO', 'codigo_practica' => '', 'descripcion_practica' => '', 'cantidad' => 1, 'valor_unitario' => 0];
    public $nuevoDiagnostico = ['cie10_codigo' => '', 'tipo_diagnostico' => 'Principal'];

    public $estadoActualUlid;
    public $observaciones;

    public function mount(SamoTramite $tramite)
    {
        $this->tramite = $tramite;
        $this->estadoActualUlid = $tramite->estado_ulid;
        $this->observaciones = $tramite->observaciones_internas;
        $this->estadosDisponibles = SamoEstado::orderBy('orden_logico')->get();

        $this->cargarRelaciones();
    }

    public function cargarRelaciones()
    {
        $this->practicas = SamoTramitePractica::where('samo_tramite_ulid', $this->tramite->ulid)->get();
        $this->diagnosticos = SamoTramiteDiagnostico::where('samo_tramite_ulid', $this->tramite->ulid)->get();
        $this->auditorias = SamoAuditoria::where('samo_tramite_ulid', $this->tramite->ulid)->orderBy('created_at', 'desc')->get();
    }

    // EL MOTOR DE TRAZABILIDAD (La Caja Negra)
    private function registrarAuditoria($accion, $detalles = null)
    {
        SamoAuditoria::create([
            'samo_tramite_ulid' => $this->tramite->ulid,
            'usuario_ulid' => Auth::id(),
            'accion' => $accion,
            'detalles' => collect($detalles)->toJson()
        ]);
        $this->cargarRelaciones();
    }

    // --- MÓDULO PRÁCTICAS ---
    public function agregarPractica()
    {
        $this->validate([
            'nuevaPractica.codigo_practica' => 'required|string',
            'nuevaPractica.cantidad' => 'required|integer|min:1',
            'nuevaPractica.valor_unitario' => 'required|numeric|min:0',
        ]);

        $subtotal = $this->nuevaPractica['cantidad'] * $this->nuevaPractica['valor_unitario'];

        SamoTramitePractica::create([
            'samo_tramite_ulid' => $this->tramite->ulid,
            'nomenclador_origen' => $this->nuevaPractica['nomenclador_origen'],
            'codigo_practica' => $this->nuevaPractica['codigo_practica'],
            'descripcion_practica' => $this->nuevaPractica['descripcion_practica'],
            'cantidad' => $this->nuevaPractica['cantidad'],
            'valor_unitario' => $this->nuevaPractica['valor_unitario'],
            'valor_subtotal' => $subtotal,
        ]);

        $this->registrarAuditoria('carga_practica', ['codigo' => $this->nuevaPractica['codigo_practica'], 'monto_agregado' => $subtotal]);

        $this->nuevaPractica = ['nomenclador_origen' => 'SAMO', 'codigo_practica' => '', 'descripcion_practica' => '', 'cantidad' => 1, 'valor_unitario' => 0];
        $this->recalcularTotal();
    }

    public function eliminarPractica($ulid)
    {
        $practica = SamoTramitePractica::find($ulid);
        if($practica) {
            $this->registrarAuditoria('elimina_practica', ['codigo' => $practica->codigo_practica, 'monto_restado' => $practica->valor_subtotal]);
            $practica->delete();
            $this->recalcularTotal();
        }
    }

    private function recalcularTotal()
    {
        $total = SamoTramitePractica::where('samo_tramite_ulid', $this->tramite->ulid)->sum('valor_subtotal');
        $this->tramite->update(['monto_total_calculado' => $total]);
        $this->cargarRelaciones();
    }

    // --- MÓDULO DIAGNÓSTICOS ---
    public function agregarDiagnostico()
    {
        $this->validate(['nuevoDiagnostico.cie10_codigo' => 'required|string']);

        SamoTramiteDiagnostico::create([
            'samo_tramite_ulid' => $this->tramite->ulid,
            'cie10_codigo' => $this->nuevoDiagnostico['cie10_codigo'],
            'tipo_diagnostico' => $this->nuevoDiagnostico['tipo_diagnostico'],
        ]);

        $this->registrarAuditoria('carga_diagnostico', ['cie10' => $this->nuevoDiagnostico['cie10_codigo'], 'tipo' => $this->nuevoDiagnostico['tipo_diagnostico']]);

        $this->nuevoDiagnostico = ['cie10_codigo' => '', 'tipo_diagnostico' => 'Principal'];
        $this->cargarRelaciones();
    }

    public function eliminarDiagnostico($ulid)
    {
        $diag = SamoTramiteDiagnostico::find($ulid);
        if($diag) {
            $this->registrarAuditoria('elimina_diagnostico', ['cie10' => $diag->cie10_codigo]);
            $diag->delete();
            $this->cargarRelaciones();
        }
    }

    // --- GUARDADO ---
    public function guardarCambiosTramite()
    {
        $estadoAnterior = $this->tramite->estado_ulid;

        $this->tramite->update([
            'estado_ulid' => $this->estadoActualUlid,
            'observaciones_internas' => $this->observaciones,
        ]);

        if($estadoAnterior !== $this->estadoActualUlid) {
            $estadoNuevoObj = SamoEstado::find($this->estadoActualUlid);
            $estadoViejoObj = SamoEstado::find($estadoAnterior);
            $this->registrarAuditoria('cambio_estado', [
                'desde' => $estadoViejoObj ? $estadoViejoObj->nombre : 'Ninguno',
                'hacia' => $estadoNuevoObj ? $estadoNuevoObj->nombre : 'Ninguno',
            ]);
        }

        session()->flash('success', 'Expediente actualizado y guardado.');
        $this->tramite->refresh();
    }
};
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-6">

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                <h3 class="font-sans font-bold text-gray-800">Información del Episodio</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">Paciente</p>
                    <p class="font-sans text-lg font-bold text-pba-blue">{{ $tramite->paciente->apellidos }}, {{ $tramite->paciente->nombres }}</p>
                    <p class="font-sans text-sm text-gray-600">DNI: {{ $tramite->paciente->documento }}</p>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">Obra Social Registrada</p>
                    <div class="inline-flex items-center px-3 py-1 rounded-lg bg-pba-cyan/10 text-pba-cyan font-bold text-sm">
                        {{ $tramite->obra_social_facturada ?: 'Ninguna (Particular)' }}
                    </div>
                </div>
                <div class="md:col-span-2 bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2">Resumen Clínico Extraído (HSI)</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Fecha Ingreso:</p>
                            <p class="text-sm font-bold text-gray-800">{{ $tramite->atencionGuardia->fecha_hora_apertura ? \Carbon\Carbon::parse($tramite->atencionGuardia->fecha_hora_apertura)->format('d/m/Y H:i') : 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Servicio/Especialidad:</p>
                            <p class="text-sm font-bold text-gray-800">{{ $tramite->atencionGuardia->servicio }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500 font-medium">Diagnóstico Médico (Texto Libre HSI):</p>
                            <p class="text-sm font-medium text-gray-700 italic">{{ $tramite->atencionGuardia->diagnostico ?: 'Sin descripción médica en el sistema.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                <h3 class="font-sans font-bold text-gray-800">Codificación CIE-10</h3>
            </div>
            <div class="p-4 border-b border-gray-100 bg-white">
                <div class="flex flex-col sm:flex-row gap-3">
                    <input wire:model="nuevoDiagnostico.cie10_codigo" type="text" placeholder="Código (Ej: J15.9)" class="w-full sm:w-1/2 rounded-xl border-gray-200 text-sm">
                    <select wire:model="nuevoDiagnostico.tipo_diagnostico" class="w-full sm:w-1/3 rounded-xl border-gray-200 text-sm">
                        <option value="Principal">Principal</option>
                        <option value="Secundario">Secundario</option>
                        <option value="Presuntivo">Presuntivo</option>
                    </select>
                    <button wire:click="agregarDiagnostico" class="w-full sm:w-auto px-4 py-2 bg-gray-800 text-white font-bold rounded-xl text-sm">Añadir</button>
                </div>
            </div>
            <table class="w-full text-left border-collapse">
                <tbody class="divide-y divide-gray-50">
                @forelse($diagnosticos as $diag)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 font-mono text-sm font-bold text-gray-800">{{ $diag->cie10_codigo }}</td>
                        <td class="px-6 py-3">
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase {{ $diag->tipo_diagnostico === 'Principal' ? 'bg-pba-blue/10 text-pba-blue' : 'bg-gray-100 text-gray-600' }}">{{ $diag->tipo_diagnostico }}</span>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <button wire:click="eliminarDiagnostico('{{ $diag->ulid }}')" class="text-red-400 hover:text-red-600 font-bold text-[10px] uppercase">Borrar</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-6 py-6 text-center text-sm text-gray-400 italic">No hay diagnósticos codificados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @canany(['ver-gestion-guardia', 'dev'])
            <div class="bg-gray-50 border border-gray-200 rounded-2xl shadow-inner overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-100">
                    <h3 class="font-sans font-bold text-gray-700 text-xs tracking-widest uppercase">Línea de Tiempo (Auditoría SAMO)</h3>
                </div>
                <div class="p-6 max-h-64 overflow-y-auto">
                    <ul class="space-y-4">
                        @forelse($auditorias as $audit)
                            <li class="relative flex gap-4">
                                <div class="w-2 h-2 mt-1.5 rounded-full bg-pba-cyan flex-shrink-0"></div>
                                <div>
                                    <p class="text-xs font-bold text-gray-800 uppercase">{{ str_replace('_', ' ', $audit->accion) }}</p>
                                    <p class="text-[10px] text-gray-500 font-mono">{{ $audit->created_at->format('d/m/Y H:i:s') }} - ID Usuario: {{ $audit->usuario_ulid }}</p>
                                    <p class="text-xs text-gray-600 mt-1 italic">{{ $audit->detalles }}</p>
                                </div>
                            </li>
                        @empty
                            <p class="text-xs text-gray-400 italic">No hay movimientos registrados.</p>
                        @endforelse
                    </ul>
                </div>
            </div>
        @endcanany

    </div>

    <div class="space-y-6">

        <div class="bg-pba-blue rounded-2xl shadow-md p-6 text-white text-center">
            <p class="text-xs text-blue-200 uppercase tracking-widest font-bold mb-2">Monto Total a Facturar</p>
            <h2 class="font-pba text-4xl font-extrabold tracking-tight">$ {{ number_format($tramite->monto_total_calculado, 2, ',', '.') }}</h2>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                <h3 class="font-sans font-bold text-gray-800 text-sm">Prácticas y Módulos</h3>
            </div>

            <div class="p-4 bg-gray-50/50 border-b border-gray-100 space-y-3">
                <div class="flex gap-2">
                    <select wire:model="nuevaPractica.nomenclador_origen" class="w-1/3 rounded-lg border-gray-200 text-xs">
                        <option value="SAMO">SAMO</option><option value="PAMI">PAMI</option><option value="IOMA">IOMA</option><option value="CUSTOM">OTRO</option>
                    </select>
                    <input wire:model="nuevaPractica.codigo_practica" type="text" placeholder="Código" class="w-2/3 rounded-lg border-gray-200 text-xs">
                </div>
                <input wire:model="nuevaPractica.descripcion_practica" type="text" placeholder="Descripción de la práctica" class="w-full rounded-lg border-gray-200 text-xs">
                <div class="flex gap-2 items-end">
                    <div class="w-1/4">
                        <label class="text-[9px] font-bold text-gray-400 uppercase">Cant.</label>
                        <input wire:model="nuevaPractica.cantidad" type="number" min="1" class="w-full rounded-lg border-gray-200 text-xs">
                    </div>
                    <div class="w-2/4">
                        <label class="text-[9px] font-bold text-gray-400 uppercase">Valor ($)</label>
                        <input wire:model="nuevaPractica.valor_unitario" type="number" step="0.01" class="w-full rounded-lg border-gray-200 text-xs">
                    </div>
                    <button wire:click="agregarPractica" class="w-1/4 py-2 bg-pba-cyan text-white font-bold rounded-lg text-xs hover:bg-teal-500">Sumar</button>
                </div>
            </div>

            <div class="max-h-60 overflow-y-auto">
                <ul class="divide-y divide-gray-50">
                    @forelse($practicas as $practica)
                        <li class="p-3 hover:bg-gray-50 transition-colors">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[9px] font-bold bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded">{{ $practica->nomenclador_origen }}</span>
                                        <span class="font-mono text-xs font-bold text-gray-800">{{ $practica->codigo_practica }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500 truncate w-40 mt-0.5">{{ $practica->descripcion_practica ?: 'Sin descripción' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-sm text-gray-800">${{ number_format($practica->valor_subtotal, 2) }}</p>
                                    <button wire:click="eliminarPractica('{{ $practica->ulid }}')" class="text-[10px] text-red-400 uppercase font-bold mt-1">Borrar</button>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="p-4 text-center text-xs text-gray-400 italic">No hay prácticas cargadas.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
            @if (session()->has('success')) <div class="mb-4 text-xs font-bold text-green-600 bg-green-50 p-2 rounded-lg text-center">{{ session('success') }}</div> @endif

            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Estado del Expediente</label>
            <select wire:model="estadoActualUlid" class="w-full rounded-xl border-gray-200 bg-gray-50 font-bold text-sm text-gray-800 mb-4">
                @foreach($estadosDisponibles as $estado) <option value="{{ $estado->ulid }}">{{ $estado->nombre }}</option> @endforeach
            </select>

            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Observaciones</label>
            <textarea wire:model="observaciones" rows="3" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm mb-4"></textarea>

            <button wire:click="guardarCambiosTramite" class="w-full py-3 bg-pba-blue hover:bg-pba-cyan text-white font-pba font-bold rounded-xl shadow-md transition-all">Guardar Expediente</button>
        </div>
    </div>
</div>
