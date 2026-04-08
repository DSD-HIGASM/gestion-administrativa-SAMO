<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SamoTramite;
use App\Models\SamoEstado;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public $facturistas = [];
    public $estadosDisponibles = [];

    // Variables de selección
    public $seleccionados = [];
    public $seleccionarPagina = false;
    public $facturistaAsignar = '';

    // Filtros
    public $search = '';
    public $filtroEstado = '';

    public function mount()
    {
        if (Auth::user()->hasAnyPermission(['ver-gestion-guardia', 'dev'])) {
            $this->facturistas = User::permission('facturar-guardia')->get();
        }
        $this->estadosDisponibles = SamoEstado::orderBy('orden_logico')->get();
    }

    // Si busca o filtra, reseteamos la página y limpiamos la selección
    public function updatingSearch() { $this->resetPage(); $this->resetSeleccion(); }
    public function updatingFiltroEstado() { $this->resetPage(); $this->resetSeleccion(); }

    // LÓGICA: SELECCIONAR TODO
    public function updatedSeleccionarPagina($value)
    {
        if ($value) {
            // Si tilda el Master Checkbox, seleccionamos todos los ULIDs de la página actual
            $this->seleccionados = $this->obtenerQuery()->paginate(15)->pluck('ulid')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->seleccionados = [];
        }
    }

    public function resetSeleccion()
    {
        $this->seleccionados = [];
        $this->seleccionarPagina = false;
    }

    // Centralizamos la consulta para reutilizarla
    public function obtenerQuery()
    {
        // Agregamos 'usuarioAsignado' para que el Jefe vea de quién es cada trámite
        $query = SamoTramite::with(['estado', 'paciente', 'atencionGuardia', 'usuarioAsignado'])
            ->whereNotNull('atencion_guardia_ulid');

        // SEGURIDAD DE ROL: Si es facturista, encerramos la consulta a su ID
        if (!Auth::user()->hasAnyPermission(['ver-gestion-guardia', 'dev'])) {
            $query->where('asignado_a_usuario_ulid', Auth::id());
        }

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('codigo_visual', 'like', '%' . $this->search . '%')
                    ->orWhereHas('paciente', function($q2) {
                        $q2->where('documento', 'like', '%' . $this->search . '%')
                            ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                            ->orWhere('nombres', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if (!empty($this->filtroEstado)) {
            $query->where('estado_ulid', $this->filtroEstado);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function asignarManual()
    {
        if (empty($this->seleccionados) || empty($this->facturistaAsignar)) return;

        SamoTramite::whereIn('ulid', $this->seleccionados)
            ->update(['asignado_a_usuario_ulid' => $this->facturistaAsignar]);

        $this->resetSeleccion();
        $this->reset(['facturistaAsignar']);
    }

    public function distribuirEquitativamente()
    {
        $tramitesLibres = SamoTramite::whereNotNull('atencion_guardia_ulid')
            ->whereNull('asignado_a_usuario_ulid')
            ->whereHas('estado', function($q) {
                $q->where('es_estado_inicial', true);
            })->get();

        if ($tramitesLibres->isEmpty() || $this->facturistas->isEmpty()) return;

        $facturistasArray = $this->facturistas->pluck('ulid')->toArray();
        $totalFacturistas = count($facturistasArray);
        $index = 0;

        foreach ($tramitesLibres as $tramite) {
            $tramite->update(['asignado_a_usuario_ulid' => $facturistasArray[$index % $totalFacturistas]]);
            $index++;
        }
        $this->resetSeleccion();
    }

    public function with(): array
    {
        return [
            'tramites' => $this->obtenerQuery()->paginate(15)
        ];
    }
};
?>

<div>
    @canany(['ver-gestion-guardia', 'dev'])
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white flex items-center justify-between">
                <div>
                    <h3 class="font-pba font-extrabold text-lg text-pba-blue tracking-tight">Panel de Jefatura: Distribución de Casos</h3>
                    <p class="font-sans text-xs text-gray-500 mt-1">Supervisa y asigna los expedientes de facturación a tu equipo.</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-pba-blue/10 flex items-center justify-center text-pba-blue">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>

            <div class="p-6 flex flex-col sm:flex-row items-center gap-6 bg-gray-50/30">
                <div class="flex flex-1 items-center gap-3 w-full">
                    <select wire:model="facturistaAsignar" class="w-full sm:w-auto rounded-xl border-gray-200 bg-white focus:border-pba-cyan focus:ring focus:ring-pba-cyan/20 font-sans text-sm text-gray-800 transition-colors shadow-sm">
                        <option value="">Seleccione un facturista...</option>
                        @foreach($facturistas as $facturista)
                            <option value="{{ $facturista->ulid }}">{{ $facturista->lastname }}, {{ $facturista->name }}</option>
                        @endforeach
                    </select>
                    <button wire:click="asignarManual" class="px-5 py-2.5 bg-pba-blue text-white rounded-xl text-sm font-pba font-bold shadow-md hover:bg-pba-cyan hover:shadow-lg transition-all disabled:opacity-50 whitespace-nowrap" @if(empty($seleccionados)) disabled @endif>
                        Asignar Seleccionados ({{ count($seleccionados) }})
                    </button>
                </div>

                <div class="h-8 w-px bg-gray-200 hidden sm:block"></div>

                <button wire:click="distribuirEquitativamente" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-xl text-sm font-pba font-bold shadow-sm hover:border-pba-cyan hover:text-pba-cyan transition-all flex items-center gap-2 whitespace-nowrap w-full sm:w-auto justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Distribuir Pendientes
                </button>
            </div>
        </div>
    @endcanany

    <div class="bg-white border border-gray-200 rounded-t-2xl shadow-sm p-4 flex flex-col sm:flex-row items-center justify-between gap-4 border-b-0">
        <div class="relative w-full sm:w-96">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por DNI, Apellido o Código..." class="pl-10 w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-pba-cyan focus:ring focus:ring-pba-cyan/20 font-sans text-sm text-gray-800 transition-colors shadow-inner">
        </div>

        <div class="w-full sm:w-auto">
            <select wire:model.live="filtroEstado" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-pba-cyan focus:ring focus:ring-pba-cyan/20 font-sans text-sm text-gray-800 transition-colors shadow-inner">
                <option value="">Todos los Estados</option>
                @foreach($estadosDisponibles as $estado)
                    <option value="{{ $estado->ulid }}">{{ $estado->nombre }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-b-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                <tr class="bg-gray-50/80 border-b border-gray-200">
                    @canany(['ver-gestion-guardia', 'dev'])
                        <th class="px-6 py-4 w-12">
                            <input type="checkbox" wire:model.live="seleccionarPagina" class="rounded border-gray-300 text-pba-cyan focus:ring-pba-cyan/30 cursor-pointer" title="Seleccionar todos en esta página">
                        </th>
                    @endcanany
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Expediente</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Fecha Ingreso</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Paciente</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Obra Social</th>

                    @canany(['ver-gestion-guardia', 'dev'])
                        <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Asignado A</th>
                    @endcanany

                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Estado</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400 text-right">Acción</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($tramites as $tramite)
                    <tr class="hover:bg-gray-50/80 transition-colors group {{ in_array($tramite->ulid, $seleccionados) ? 'bg-pba-cyan/5' : '' }}">

                        @canany(['ver-gestion-guardia', 'dev'])
                            <td class="px-6 py-4">
                                <input type="checkbox" wire:model.live="seleccionados" value="{{ $tramite->ulid }}" class="rounded border-gray-300 text-pba-cyan focus:ring-pba-cyan/30 cursor-pointer">
                            </td>
                        @endcanany

                        <td class="px-6 py-4">
                            <span class="font-mono text-sm font-bold text-pba-cyan bg-pba-cyan/10 px-2 py-1 rounded-md">{{ $tramite->codigo_visual }}</span>
                        </td>
                        <td class="px-6 py-4 font-sans text-xs text-gray-500 font-medium">
                            {{ $tramite->atencionGuardia->fecha_hora_apertura ? \Carbon\Carbon::parse($tramite->atencionGuardia->fecha_hora_apertura)->format('d/m/Y H:i') : 'Sin registro' }}
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-sans text-sm font-bold text-gray-800">{{ $tramite->paciente->apellidos ?? '' }}, {{ $tramite->paciente->nombres ?? '' }}</p>
                            <p class="font-sans text-[11px] text-gray-500 mt-0.5">DNI: {{ $tramite->paciente->documento ?? 'N/A' }}</p>
                        </td>
                        <td class="px-6 py-4 font-sans text-sm text-gray-600 font-medium">
                            {{ $tramite->obra_social_facturada ?: 'Sin OS registrada' }}
                        </td>

                        @canany(['ver-gestion-guardia', 'dev'])
                            <td class="px-6 py-4">
                                @if($tramite->usuarioAsignado)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-pba-blue/10 flex items-center justify-center text-pba-blue font-pba font-bold text-[9px] uppercase">
                                            {{ substr($tramite->usuarioAsignado->name, 0, 1) }}{{ substr($tramite->usuarioAsignado->lastname, 0, 1) }}
                                        </div>
                                        <span class="font-sans text-xs text-gray-700 font-medium">{{ $tramite->usuarioAsignado->lastname }}</span>
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-bold bg-orange-50 text-orange-600 border border-orange-100 uppercase">
                                        <div class="w-1.5 h-1.5 rounded-full bg-orange-400"></div> Sin asignar
                                    </span>
                                @endif
                            </td>
                        @endcanany

                        <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold text-white uppercase tracking-wider shadow-sm" style="background-color: {{ $tramite->estado->color_hex ?? '#000' }}">
                                    {{ $tramite->estado->nombre ?? 'Desconocido' }}
                                </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="#" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl transition-all border border-gray-200 text-gray-600 hover:border-pba-cyan hover:text-pba-cyan hover:bg-pba-cyan/5 bg-white shadow-sm hover:shadow">
                                Abrir <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-16 text-center text-gray-500 font-sans">
                            <div class="mx-auto w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 border border-gray-100 shadow-inner">
                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <p class="text-gray-800 font-extrabold mb-1">Bandeja Vacía</p>
                            <p class="text-xs text-gray-500">No se encontraron expedientes con los filtros actuales.</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($tramites->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                {{ $tramites->links() }}
            </div>
        @endif
    </div>
</div>
