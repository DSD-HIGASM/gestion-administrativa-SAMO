<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\SamoTramite;
use App\Models\SamoTramitePractica;
use App\Models\SamoTramiteDiagnostico;
use App\Models\SamoDocumento;
use App\Models\SamoEstado;
use App\Models\SamoAuditoria;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public SamoTramite $tramite;

    public $practicas = [];
    public $diagnosticos = [];
    public $documentos = [];
    public $auditorias = [];
    public $estadosDisponibles = [];
    public $usuariosCache = [];

    public $nuevaPractica = ['nomenclador_origen' => 'SAMO', 'codigo_practica' => '', 'descripcion_practica' => '', 'cantidad' => 1, 'valor_unitario' => 0];
    public $nuevoDiagnostico = ['cie10_codigo' => '', 'tipo_diagnostico' => 'Principal'];

    public $archivoNuevo;
    public $nombreArchivoCustom = '';

    public $estadoActualUlid;
    public $observaciones;

    public function mount(SamoTramite $tramite)
    {
        $this->tramite = $tramite;
        $this->estadoActualUlid = $tramite->estado_ulid;
        $this->observaciones = $tramite->observaciones_internas;
        $this->estadosDisponibles = SamoEstado::orderBy('orden_logico')->get();

        $this->cargarRelaciones();
        $this->registrarAuditoria('apertura_expediente', ['metodo' => 'Ingreso a la vista detallada', 'estado_al_abrir' => $this->obtenerNombreEstado($this->estadoActualUlid)]);
    }

    public function cargarRelaciones()
    {
        $this->practicas = SamoTramitePractica::where('samo_tramite_ulid', $this->tramite->ulid)->get();
        $this->diagnosticos = SamoTramiteDiagnostico::where('samo_tramite_ulid', $this->tramite->ulid)->get();
        $this->documentos = SamoDocumento::where('samo_tramite_ulid', $this->tramite->ulid)->get();
        $this->auditorias = SamoAuditoria::where('samo_tramite_ulid', $this->tramite->ulid)->orderBy('created_at', 'desc')->get();

        $userIds = $this->auditorias->pluck('usuario_ulid')->unique()->filter();
        if($userIds->isNotEmpty()) {
            $this->usuariosCache = User::withTrashed()->whereIn('ulid', $userIds)->get()->keyBy('ulid');
        }
    }

    private function obtenerNombreEstado($ulid)
    {
        return collect($this->estadosDisponibles)->firstWhere('ulid', $ulid)->nombre ?? 'Desconocido';
    }

    private function registrarAuditoria($accion, $detalles = [])
    {
        $detalles['ip'] = request()->ip();

        SamoAuditoria::create([
            'samo_tramite_ulid' => $this->tramite->ulid,
            'usuario_ulid' => Auth::id(),
            'accion' => $accion,
            'detalles' => json_encode($detalles, JSON_UNESCAPED_UNICODE)
        ]);
        $this->cargarRelaciones();
    }

    public function registrarCierreAbandonado()
    {
        $this->registrarAuditoria('cierre_expediente_forzado', ['metodo' => 'Abandono de pestaña/navegador']);
    }

    public function agregarPractica()
    {
        $this->validate([
            'nuevaPractica.codigo_practica' => 'required|string',
            'nuevaPractica.cantidad' => 'required|integer|min:1',
            'nuevaPractica.valor_unitario' => 'required|numeric|min:0',
        ]);

        $subtotal = $this->nuevaPractica['cantidad'] * $this->nuevaPractica['valor_unitario'];

        $practica = SamoTramitePractica::create([
            'samo_tramite_ulid' => $this->tramite->ulid,
            'nomenclador_origen' => $this->nuevaPractica['nomenclador_origen'],
            'codigo_practica' => $this->nuevaPractica['codigo_practica'],
            'descripcion_practica' => $this->nuevaPractica['descripcion_practica'],
            'cantidad' => $this->nuevaPractica['cantidad'],
            'valor_unitario' => $this->nuevaPractica['valor_unitario'],
            'valor_subtotal' => $subtotal,
        ]);

        $this->registrarAuditoria('carga_practica', [
            'codigo' => $practica->codigo_practica,
            'cantidad' => $practica->cantidad,
            'valor_unitario' => $practica->valor_unitario,
            'monto_agregado' => $subtotal
        ]);

        $this->nuevaPractica = ['nomenclador_origen' => 'SAMO', 'codigo_practica' => '', 'descripcion_practica' => '', 'cantidad' => 1, 'valor_unitario' => 0];
        $this->recalcularTotal();
    }

    public function eliminarPractica($ulid)
    {
        $practica = SamoTramitePractica::find($ulid);
        if($practica) {
            $this->registrarAuditoria('elimina_practica', [
                'codigo_borrado' => $practica->codigo_practica,
                'monto_restado' => $practica->valor_subtotal
            ]);
            $practica->delete();
            $this->recalcularTotal();
        }
    }

    private function recalcularTotal()
    {
        $total = SamoTramitePractica::where('samo_tramite_ulid', $this->tramite->ulid)->sum('valor_subtotal');
        $totalAnterior = $this->tramite->monto_total_calculado;
        $this->tramite->update(['monto_total_calculado' => $total]);

        if($totalAnterior != $total) {
            $this->registrarAuditoria('recalculo_total', [
                'total_anterior' => $totalAnterior,
                'total_nuevo' => $total,
                'diferencia' => $total - $totalAnterior
            ]);
        }
        $this->cargarRelaciones();
    }

    public function agregarDiagnostico()
    {
        $this->validate(['nuevoDiagnostico.cie10_codigo' => 'required|string']);

        $diag = SamoTramiteDiagnostico::create([
            'samo_tramite_ulid' => $this->tramite->ulid,
            'cie10_codigo' => $this->nuevoDiagnostico['cie10_codigo'],
            'tipo_diagnostico' => $this->nuevoDiagnostico['tipo_diagnostico'],
        ]);

        $this->registrarAuditoria('carga_diagnostico', [
            'cie10' => $diag->cie10_codigo,
            'tipo' => $diag->tipo_diagnostico
        ]);

        $this->nuevoDiagnostico = ['cie10_codigo' => '', 'tipo_diagnostico' => 'Principal'];
        $this->cargarRelaciones();
    }

    public function eliminarDiagnostico($ulid)
    {
        $diag = SamoTramiteDiagnostico::find($ulid);
        if($diag) {
            $this->registrarAuditoria('elimina_diagnostico', ['cie10_borrado' => $diag->cie10_codigo]);
            $diag->delete();
            $this->cargarRelaciones();
        }
    }

    public function subirDocumento()
    {
        $this->validate([
            'archivoNuevo' => 'required|file|max:5120',
            'nombreArchivoCustom' => 'required|string|max:255'
        ]);

        $extension = $this->archivoNuevo->getClientOriginalExtension();
        $nombreFinal = \Illuminate\Support\Str::slug($this->nombreArchivoCustom) . '_' . time() . '.' . $extension;
        $ruta = $this->archivoNuevo->storeAs('samo_documentos/' . $this->tramite->ulid, $nombreFinal, 'public');

        SamoDocumento::create([
            'samo_tramite_ulid' => $this->tramite->ulid,
            'paciente_ulid' => $this->tramite->paciente_ulid,
            'nombre_original' => $this->nombreArchivoCustom, // Solo guardamos los campos que existen en la BD
            'ruta_archivo' => $ruta,
        ]);

        $this->registrarAuditoria('sube_documento', ['nombre_archivo' => $this->nombreArchivoCustom, 'extension' => $extension]);
        $this->reset(['archivoNuevo', 'nombreArchivoCustom']);
        $this->cargarRelaciones();
        session()->flash('doc_success', 'Documento adjuntado correctamente.');
    }

    public function descargarDocumento($ulid)
    {
        $doc = SamoDocumento::find($ulid);
        if ($doc && Storage::disk('public')->exists($doc->ruta_archivo)) {
            $this->registrarAuditoria('descarga_documento', ['nombre_archivo' => $doc->nombre_original]);
            $ext = pathinfo($doc->ruta_archivo, PATHINFO_EXTENSION);
            return Storage::disk('public')->download($doc->ruta_archivo, $doc->nombre_original . '.' . $ext);
        }
        session()->flash('doc_error', 'El archivo no se encuentra en el servidor.');
    }

    public function eliminarDocumento($ulid)
    {
        $doc = SamoDocumento::find($ulid);
        if ($doc) {
            Storage::disk('public')->delete($doc->ruta_archivo);
            $this->registrarAuditoria('elimina_documento', ['nombre_archivo' => $doc->nombre_original]);
            $doc->delete();
            $this->cargarRelaciones();
        }
    }

    public function guardarCambiosTramite()
    {
        $estadoAnterior = $this->tramite->estado_ulid;
        $obsAnterior = $this->tramite->observaciones_internas;

        $this->tramite->update([
            'estado_ulid' => $this->estadoActualUlid,
            'observaciones_internas' => $this->observaciones,
        ]);

        if($estadoAnterior !== $this->estadoActualUlid) {
            $this->registrarAuditoria('cambio_estado', [
                'estado_anterior' => $this->obtenerNombreEstado($estadoAnterior),
                'estado_nuevo' => $this->obtenerNombreEstado($this->estadoActualUlid)
            ]);
        }

        if($obsAnterior !== $this->observaciones) {
            $this->registrarAuditoria('actualiza_observaciones', [
                'observacion_anterior' => $obsAnterior ?: 'Vacio',
                'observacion_nueva' => $this->observaciones ?: 'Vacio'
            ]);
        }

        $this->registrarAuditoria('guardado_manual', ['metodo' => 'Botón de guardado general']);
        session()->flash('success', 'Expediente actualizado y guardado.');
        $this->tramite->refresh();
    }

    public function guardarYCerrar()
    {
        $this->guardarCambiosTramite();
        $this->registrarAuditoria('cierre_expediente_controlado', ['metodo' => 'Botón Guardar y Volver']);
        return redirect()->route('samo.guardia.index');
    }
};
?>

<div x-data="{ tab: 'resumen' }" @beforeunload.window="$wire.registrarCierreAbandonado()">

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-4 w-full md:w-auto">
            <select wire:model="estadoActualUlid" class="rounded-xl border-gray-200 bg-gray-50 font-bold text-sm text-gray-800 focus:ring-pba-cyan w-full md:w-64">
                @foreach($estadosDisponibles as $estado) <option value="{{ $estado->ulid }}">{{ $estado->nombre }}</option> @endforeach
            </select>
            <button wire:click="guardarCambiosTramite" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-sm transition-colors shadow-sm whitespace-nowrap">Guardar Progreso</button>
        </div>
        <button wire:click="guardarYCerrar" class="w-full md:w-auto px-6 py-2 bg-pba-blue hover:bg-pba-cyan text-white font-pba font-bold rounded-xl shadow-md transition-all flex justify-center items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Guardar y Volver
        </button>
    </div>

    @if (session()->has('success')) <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="mb-6 text-sm font-bold text-green-700 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-xl shadow-sm">{{ session('success') }}</div> @endif

    <div class="flex space-x-1 bg-gray-200/50 p-1 rounded-t-2xl overflow-x-auto">
        <button @click="tab = 'resumen'" :class="{ 'bg-white shadow-sm text-pba-blue font-extrabold': tab === 'resumen', 'text-gray-500 hover:text-gray-700 font-medium': tab !== 'resumen' }" class="px-6 py-3 rounded-t-xl text-sm transition-all whitespace-nowrap">Clínica y Facturación</button>
        <button @click="tab = 'paciente'" :class="{ 'bg-white shadow-sm text-pba-blue font-extrabold': tab === 'paciente', 'text-gray-500 hover:text-gray-700 font-medium': tab !== 'paciente' }" class="px-6 py-3 rounded-t-xl text-sm transition-all whitespace-nowrap flex items-center gap-2">Perfil Paciente</button>
        <button @click="tab = 'documentos'" :class="{ 'bg-white shadow-sm text-pba-blue font-extrabold': tab === 'documentos', 'text-gray-500 hover:text-gray-700 font-medium': tab !== 'documentos' }" class="px-6 py-3 rounded-t-xl text-sm transition-all whitespace-nowrap flex items-center gap-2">
            Documentos @if(count($documentos) > 0) <span class="bg-pba-cyan text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ count($documentos) }}</span> @endif
        </button>
        @canany(['ver-gestion-guardia', 'dev'])
            <button @click="tab = 'auditoria'" :class="{ 'bg-white shadow-sm text-pba-blue font-extrabold': tab === 'auditoria', 'text-gray-500 hover:text-gray-700 font-medium': tab !== 'auditoria' }" class="px-6 py-3 rounded-t-xl text-sm transition-all whitespace-nowrap flex items-center gap-2">Auditoría Integral</button>
        @endcanany
    </div>

    <div class="bg-white border border-gray-200 border-t-0 rounded-b-2xl shadow-sm p-6 min-h-[500px]">

        <div x-show="tab === 'resumen'" x-transition.opacity class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-6">
                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100">
                    <div class="flex items-center gap-3 mb-4 border-b border-gray-200 pb-3">
                        <div class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg></div>
                        <h3 class="font-pba font-bold text-gray-800 text-lg">Episodio de Guardia</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Ingreso HSI</p><p class="font-bold text-sm text-gray-800">{{ $tramite->atencionGuardia->fecha_hora_apertura ? \Carbon\Carbon::parse($tramite->atencionGuardia->fecha_hora_apertura)->format('d/m/Y H:i') : 'N/A' }}</p></div>
                        <div><p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Egreso HSI</p><p class="font-bold text-sm text-gray-800">{{ $tramite->atencionGuardia->fecha_hora_alta ? \Carbon\Carbon::parse($tramite->atencionGuardia->fecha_hora_alta)->format('d/m/Y H:i') : 'Sin alta registrada' }}</p></div>
                        <div><p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Servicio</p><p class="font-bold text-sm text-gray-800">{{ $tramite->atencionGuardia->servicio }}</p></div>
                        <div><p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Triage</p><p class="font-bold text-sm text-gray-800">{{ $tramite->atencionGuardia->cantidad_triage ?: 'No registrado' }}</p></div>
                        <div class="col-span-2 bg-white p-3 rounded-lg border border-gray-200 mt-2">
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Diagnóstico Médico (Texto HSI)</p>
                            <p class="text-sm font-medium text-gray-700 italic">{{ $tramite->atencionGuardia->diagnostico ?: 'Sin descripción médica extraída.' }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-sans font-bold text-gray-800 mb-3 border-b border-gray-100 pb-2">Codificación CIE-10</h3>
                    <div class="flex flex-col sm:flex-row gap-2 mb-4">
                        <input wire:model="nuevoDiagnostico.cie10_codigo" type="text" placeholder="Cód (Ej: J15)" class="w-full sm:w-1/2 rounded-xl border-gray-200 text-sm focus:ring-pba-cyan">
                        <select wire:model="nuevoDiagnostico.tipo_diagnostico" class="w-full sm:w-1/3 rounded-xl border-gray-200 text-sm focus:ring-pba-cyan">
                            <option value="Principal">Principal</option><option value="Secundario">Secundario</option><option value="Presuntivo">Presuntivo</option>
                        </select>
                        <button wire:click="agregarDiagnostico" class="px-4 py-2 bg-gray-800 text-white font-bold rounded-xl text-sm">Añadir</button>
                    </div>
                    <ul class="divide-y divide-gray-100 border border-gray-100 rounded-xl">
                        @forelse($diagnosticos as $diag)
                            <li class="p-3 flex justify-between items-center hover:bg-gray-50">
                                <div><span class="font-mono font-bold text-sm text-gray-800 mr-2">{{ $diag->cie10_codigo }}</span><span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $diag->tipo_diagnostico === 'Principal' ? 'bg-pba-blue/10 text-pba-blue' : 'bg-gray-100 text-gray-600' }}">{{ $diag->tipo_diagnostico }}</span></div>
                                <button wire:click="eliminarDiagnostico('{{ $diag->ulid }}')" class="text-red-400 hover:text-red-600 font-bold text-[10px] uppercase">Borrar</button>
                            </li>
                        @empty
                            <li class="p-3 text-center text-xs text-gray-400 italic">Sin codificar.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-gradient-to-br from-pba-blue to-pba-cyan rounded-2xl shadow-md p-6 text-white text-center relative overflow-hidden">
                    <div class="absolute inset-0 bg-white opacity-10" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 10px 10px;"></div>
                    <div class="relative z-10">
                        <p class="text-xs text-blue-100 uppercase tracking-widest font-bold mb-1">Monto Total a Facturar</p>
                        <h2 class="font-pba text-4xl font-extrabold tracking-tight">$ {{ number_format($tramite->monto_total_calculado, 2, ',', '.') }}</h2>
                        <p class="text-xs text-blue-100 mt-2 font-medium">Obra Social: {{ $tramite->obra_social_facturada ?: 'Particular' }}</p>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-100"><h3 class="font-sans font-bold text-gray-800 text-sm">Prácticas y Módulos SAMO</h3></div>
                    <div class="p-4 bg-white border-b border-gray-100 space-y-3">
                        <div class="flex gap-2">
                            <select wire:model="nuevaPractica.nomenclador_origen" class="w-1/3 rounded-lg border-gray-200 text-xs focus:ring-pba-cyan"><option value="SAMO">SAMO</option><option value="PAMI">PAMI</option><option value="IOMA">IOMA</option><option value="CUSTOM">OTRO</option></select>
                            <input wire:model="nuevaPractica.codigo_practica" type="text" placeholder="Cód. Práctica" class="w-2/3 rounded-lg border-gray-200 text-xs focus:ring-pba-cyan">
                        </div>
                        <input wire:model="nuevaPractica.descripcion_practica" type="text" placeholder="Descripción de la práctica" class="w-full rounded-lg border-gray-200 text-xs focus:ring-pba-cyan">
                        <div class="flex gap-2 items-end">
                            <div class="w-1/4"><label class="text-[9px] font-bold text-gray-400 uppercase">Cant.</label><input wire:model="nuevaPractica.cantidad" type="number" min="1" class="w-full rounded-lg border-gray-200 text-xs focus:ring-pba-cyan"></div>
                            <div class="w-2/4"><label class="text-[9px] font-bold text-gray-400 uppercase">Valor ($)</label><input wire:model="nuevaPractica.valor_unitario" type="number" step="0.01" class="w-full rounded-lg border-gray-200 text-xs focus:ring-pba-cyan"></div>
                            <button wire:click="agregarPractica" class="w-1/4 py-2 bg-pba-cyan text-white font-bold rounded-lg text-xs hover:bg-teal-500 transition-colors">Sumar</button>
                        </div>
                    </div>
                    <div class="max-h-64 overflow-y-auto bg-gray-50/30">
                        <ul class="divide-y divide-gray-100">
                            @forelse($practicas as $practica)
                                <li class="p-3 hover:bg-white transition-colors">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="flex items-center gap-1.5"><span class="text-[9px] font-bold bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded">{{ $practica->nomenclador_origen }}</span><span class="font-mono text-xs font-bold text-gray-800">{{ $practica->codigo_practica }}</span></div>
                                            <p class="text-xs text-gray-500 truncate w-48 mt-0.5" title="{{ $practica->descripcion_practica }}">{{ $practica->descripcion_practica ?: 'Sin descripción' }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-sm text-gray-800">${{ number_format($practica->valor_subtotal, 2) }}</p>
                                            <p class="text-[9px] text-gray-400 mb-1">{{ $practica->cantidad }} x ${{ $practica->valor_unitario }}</p>
                                            <button wire:click="eliminarPractica('{{ $practica->ulid }}')" class="text-[10px] text-red-400 uppercase font-bold">Borrar</button>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="p-6 text-center text-xs text-gray-400 italic">El carrito de prácticas está vacío.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="tab === 'paciente'" x-transition.opacity style="display: none;" class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-pba-blue/5 px-8 py-6 border-b border-gray-100 flex items-center gap-4">
                    <div class="w-16 h-16 bg-pba-blue text-white rounded-full flex items-center justify-center font-pba text-2xl font-bold shadow-md">
                        {{ substr($tramite->paciente->nombres, 0, 1) }}{{ substr($tramite->paciente->apellidos, 0, 1) }}
                    </div>
                    <div>
                        <h2 class="text-2xl font-pba font-bold text-gray-800">{{ $tramite->paciente->apellidos }}, {{ $tramite->paciente->nombres }}</h2>
                        <div class="flex gap-3 mt-1">
                            <span class="bg-white border border-gray-200 px-2 py-0.5 rounded text-xs font-bold text-gray-600">{{ $tramite->paciente->tipo_documento }}: {{ $tramite->paciente->documento }}</span>
                            @if($tramite->paciente->id_paciente_hsi) <span class="bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded text-xs font-bold text-indigo-600">ID HSI: {{ $tramite->paciente->id_paciente_hsi }}</span> @endif
                        </div>
                    </div>
                </div>
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <h4 class="font-bold text-gray-800 border-b border-gray-100 pb-2">Datos Demográficos</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div><p class="text-[10px] text-gray-400 font-bold uppercase">Nacimiento</p><p class="text-sm font-medium text-gray-800">{{ $tramite->paciente->fecha_nacimiento ? \Carbon\Carbon::parse($tramite->paciente->fecha_nacimiento)->format('d/m/Y') : 'No registrado' }}</p></div>
                            <div><p class="text-[10px] text-gray-400 font-bold uppercase">Edad</p><p class="text-sm font-medium text-gray-800">{{ $tramite->paciente->fecha_nacimiento ? \Carbon\Carbon::parse($tramite->paciente->fecha_nacimiento)->age . ' años' : 'N/A' }}</p></div>
                            <div><p class="text-[10px] text-gray-400 font-bold uppercase">Sexo Registrado</p><p class="text-sm font-medium text-gray-800">{{ $tramite->paciente->sexo ?: 'No registrado' }}</p></div>
                            <div><p class="text-[10px] text-gray-400 font-bold uppercase">Teléfono</p><p class="text-sm font-medium text-gray-800">{{ $tramite->paciente->telefono ?: 'Sin teléfono' }}</p></div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <h4 class="font-bold text-gray-800 border-b border-gray-100 pb-2">Cobertura Financiera</h4>
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Obra Social Facturada (Episodio)</p>
                            <p class="text-lg font-bold text-pba-cyan">{{ $tramite->obra_social_facturada ?: 'Ninguna (Arancelado)' }}</p>
                            @if($tramite->numero_afiliado_facturado) <p class="text-xs text-gray-500 mt-1 font-mono">Afiliado N°: {{ $tramite->numero_afiliado_facturado }}</p> @endif
                        </div>
                        <div class="mt-4">
                            <label class="text-[10px] text-gray-400 font-bold uppercase block mb-1">Observaciones Internas</label>
                            <textarea wire:model="observaciones" rows="4" class="w-full rounded-xl border-gray-200 text-sm focus:ring-pba-cyan bg-yellow-50" placeholder="Escribe notas permanentes aquí..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="tab === 'documentos'" x-transition.opacity style="display: none;" class="max-w-4xl mx-auto">
            @if (session()->has('doc_success')) <div class="mb-4 p-3 bg-green-50 text-green-700 text-sm font-bold rounded-lg border border-green-200">{{ session('doc_success') }}</div> @endif
            @if (session()->has('doc_error')) <div class="mb-4 p-3 bg-red-50 text-red-700 text-sm font-bold rounded-lg border border-red-200">{{ session('doc_error') }}</div> @endif

            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6 mb-6">
                <h3 class="font-bold text-gray-800 mb-4">Adjuntar Nuevo Documento</h3>
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1 w-full">
                        <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Nombre Descriptivo</label>
                        <input wire:model="nombreArchivoCustom" type="text" placeholder="Ej: DNI Frente, Carnet IOMA, Bono Autorización" class="w-full rounded-xl border-gray-200 text-sm focus:ring-pba-cyan">
                        @error('nombreArchivoCustom') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex-1 w-full">
                        <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Archivo (Máx 5MB)</label>
                        <input wire:model="archivoNuevo" type="file" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-pba-blue/10 file:text-pba-blue hover:file:bg-pba-blue/20 cursor-pointer">
                        @error('archivoNuevo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <button wire:click="subirDocumento" class="px-6 py-2.5 bg-gray-800 hover:bg-black text-white font-bold rounded-xl text-sm transition-colors shadow-md disabled:opacity-50" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="subirDocumento">Subir</span>
                        <span wire:loading wire:target="subirDocumento">Espere...</span>
                    </button>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b border-gray-100">
                    <tr><th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Documento</th><th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Formato</th><th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Fecha</th><th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Acciones</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($documentos as $doc)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-bold text-gray-800 text-sm">{{ $doc->nombre_original }}</td>
                            <td class="px-6 py-4"><span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs font-bold uppercase rounded">{{ pathinfo($doc->ruta_archivo, PATHINFO_EXTENSION) }}</span></td>
                            <td class="px-6 py-4 text-xs text-gray-500">{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-right space-x-3">
                                <button wire:click="descargarDocumento('{{ $doc->ulid }}')" class="text-pba-blue hover:text-pba-cyan font-bold text-xs uppercase">Bajar</button>
                                <button wire:click="eliminarDocumento('{{ $doc->ulid }}')" class="text-red-500 hover:text-red-700 font-bold text-xs uppercase">Borrar</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">No hay documentos adjuntos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @canany(['ver-gestion-guardia', 'dev'])
            <div x-show="tab === 'auditoria'" x-transition.opacity style="display: none;" class="max-w-4xl mx-auto">
                <div class="bg-gray-900 rounded-2xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-700 bg-gray-800 flex items-center justify-between">
                        <h3 class="font-mono font-bold text-green-400 text-sm tracking-widest uppercase">Caja Negra: Trazabilidad del Expediente</h3>
                        <span class="text-xs text-gray-400 font-mono">Más recientes primero</span>
                    </div>
                    <div class="p-6 max-h-[600px] overflow-y-auto custom-scrollbar">
                        <div class="relative border-l border-gray-700 ml-3 space-y-6">
                            @forelse($auditorias as $audit)
                                @php
                                    $usuario = $usuariosCache->get($audit->usuario_ulid);
                                    $nombreUsuario = $usuario ? "{$usuario->name} {$usuario->lastname}" : 'Sistema / Eliminado';
                                    $detalles = json_decode($audit->detalles, true) ?? [];
                                    $ip = $detalles['ip'] ?? 'Desconocida';
                                    unset($detalles['ip']);
                                @endphp
                                <div class="relative pl-6">
                                    <div class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 rounded-full {{ str_contains($audit->accion, 'elimina') || str_contains($audit->accion, 'forzado') ? 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.6)]' : (str_contains($audit->accion, 'cierre') || str_contains($audit->accion, 'estado') ? 'bg-orange-500 shadow-[0_0_8px_rgba(249,115,22,0.6)]' : 'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)]') }}"></div>
                                    <div>
                                        <div class="flex items-baseline gap-3 mb-1">
                                            <span class="text-sm font-bold text-gray-100 uppercase tracking-wider">{{ str_replace('_', ' ', $audit->accion) }}</span>
                                            <span class="text-[10px] text-gray-500 font-mono">{{ $audit->created_at->format('d/m/Y H:i:s') }}</span>
                                        </div>
                                        <div class="mb-2 text-xs text-gray-400 font-mono flex gap-3">
                                            <span>👤 {{ $nombreUsuario }}</span>
                                            <span>🌐 {{ $ip }}</span>
                                        </div>
                                        <div class="bg-gray-800 p-3 rounded border border-gray-700 text-xs font-mono">
                                            @foreach($detalles as $key => $value)
                                                <p><span class="text-pba-cyan">{{ strtoupper(str_replace('_', ' ', $key)) }}:</span> <span class="text-gray-300">{{ is_array($value) ? json_encode($value) : $value }}</span></p>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="pl-6 text-xs text-gray-500 italic font-mono">No hay registros de auditoría disponibles.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <style> .custom-scrollbar::-webkit-scrollbar { width: 6px; } .custom-scrollbar::-webkit-scrollbar-track { background: #1f2937; } .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 4px; } </style>
            </div>
        @endcanany

    </div>
</div>
