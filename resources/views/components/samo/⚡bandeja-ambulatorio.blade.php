<?php

use Livewire\Component;
use App\Models\SamoTramite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public $tramites = [];
    public $facturistas = [];

    public $seleccionados = [];
    public $facturistaAsignar = '';

    public function mount()
    {
        $this->cargarDatos();

        if (Auth::user()->hasAnyPermission(['ver-gestion-ambulatorio', 'dev'])) {
            $this->facturistas = User::permission(['facturar-ambulatorio-baja', 'facturar-ambulatorio-alta'])->get();
        }
    }

    public function cargarDatos()
    {
        $query = SamoTramite::with(['estado', 'paciente'])
            ->whereNotNull('atencion_ambulatorio_ulid');

        if (!Auth::user()->hasAnyPermission(['ver-gestion-ambulatorio', 'dev'])) {
            $query->where('asignado_a_usuario_ulid', Auth::id());
        }

        $this->tramites = $query->orderBy('created_at', 'desc')->get();
    }

    public function asignarManual()
    {
        if (empty($this->seleccionados) || empty($this->facturistaAsignar)) {
            return;
        }

        SamoTramite::whereIn('ulid', $this->seleccionados)
            ->update(['asignado_a_usuario_ulid' => $this->facturistaAsignar]);

        $this->reset(['seleccionados', 'facturistaAsignar']);
        $this->cargarDatos();
    }

    public function distribuirEquitativamente()
    {
        $tramitesLibres = SamoTramite::whereNotNull('atencion_ambulatorio_ulid')
            ->whereNull('asignado_a_usuario_ulid')
            ->whereHas('estado', function($q) {
                $q->where('es_estado_inicial', true);
            })->get();

        if ($tramitesLibres->isEmpty() || $this->facturistas->isEmpty()) {
            return;
        }

        $facturistasArray = $this->facturistas->pluck('ulid')->toArray();
        $totalFacturistas = count($facturistasArray);
        $index = 0;

        foreach ($tramitesLibres as $tramite) {
            $tramite->update([
                'asignado_a_usuario_ulid' => $facturistasArray[$index % $totalFacturistas]
            ]);
            $index++;
        }

        $this->cargarDatos();
    }
};
?>

<div>
    @canany(['ver-gestion-ambulatorio', 'dev'])
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white">
                <h3 class="font-pba font-extrabold text-lg text-pba-blue tracking-tight">Panel de Jefatura: Consultorios Externos</h3>
                <p class="font-sans text-xs text-gray-500 mt-1">Asigna manualmente o distribuye la carga equitativamente.</p>
            </div>

            <div class="p-6 flex flex-wrap items-center gap-6">
                <div class="flex items-center gap-3">
                    <select wire:model="facturistaAsignar" class="rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-pba-cyan focus:ring focus:ring-pba-cyan/20 font-sans text-sm text-gray-800 transition-colors">
                        <option value="">Seleccione un facturista...</option>
                        @foreach($facturistas as $facturista)
                            <option value="{{ $facturista->ulid }}">{{ $facturista->lastname }}, {{ $facturista->name }}</option>
                        @endforeach
                    </select>
                    <button wire:click="asignarManual" class="px-5 py-2.5 bg-pba-blue text-white rounded-xl text-sm font-pba font-bold shadow-md hover:bg-pba-cyan hover:shadow-lg transition-all">
                        Asignar Seleccionados
                    </button>
                </div>

                <div class="h-8 w-px bg-gray-200 hidden sm:block"></div>

                <button wire:click="distribuirEquitativamente" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-xl text-sm font-pba font-bold shadow-sm hover:border-pba-cyan hover:text-pba-cyan transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Distribuir Equitativamente
                </button>
            </div>
        </div>
    @endcanany

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    @canany(['ver-gestion-ambulatorio', 'dev'])
                        <th class="px-6 py-4 w-12"><input type="checkbox" disabled class="rounded border-gray-300 text-pba-cyan focus:ring-pba-cyan"></th>
                    @endcanany
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Código</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Paciente</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Documento</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Obra Social</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Estado</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400 text-right">Acción</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                @forelse($tramites as $tramite)
                    <tr class="hover:bg-gray-50/80 transition-colors group">
                        @canany(['ver-gestion-ambulatorio', 'dev'])
                            <td class="px-6 py-4">
                                <input type="checkbox" wire:model="seleccionados" value="{{ $tramite->ulid }}" class="rounded border-gray-300 text-pba-cyan focus:ring-pba-cyan/30">
                            </td>
                        @endcanany
                        <td class="px-6 py-4 font-mono text-sm font-bold text-pba-cyan">{{ $tramite->codigo_visual }}</td>
                        <td class="px-6 py-4">
                            <p class="font-sans text-sm font-bold text-gray-800">{{ $tramite->paciente->apellidos ?? '' }}, {{ $tramite->paciente->nombres ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4 font-sans text-sm font-medium text-gray-600">{{ $tramite->paciente->documento ?? 'N/A' }}</td>
                        <td class="px-6 py-4 font-sans text-sm text-gray-500">{{ $tramite->obra_social_facturada ?: 'Sin OS registrada' }}</td>
                        <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold text-white uppercase tracking-wider" style="background-color: {{ $tramite->estado->color_hex ?? '#000' }}">
                                    {{ $tramite->estado->nombre ?? 'Desconocido' }}
                                </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="#" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-lg transition-all border border-gray-200 text-gray-600 hover:border-pba-cyan hover:text-pba-cyan bg-white shadow-sm">
                                Abrir <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 font-sans">
                            <p>No hay expedientes en tu bandeja de Ambulatorio.</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
