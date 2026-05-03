<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Nomenclador;

new class extends Component {
    use WithPagination;

    // Filtros de tabla
    public $search = '';
    public $filtroOrigen = '';
    public $filtroActivo = '';

    // Formulario (Alta/Edición)
    public $modoEdicion = false;
    public $practicaUlid = null;
    public $formOrigen = 'SAMO';
    public $formCodigo = '';
    public $formDescripcion = '';
    public $formValor = '';
    public $formActivo = true;

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFiltroOrigen() { $this->resetPage(); }
    public function updatingFiltroActivo() { $this->resetPage(); }

    public function guardarPractica()
    {
        $this->validate([
            'formOrigen' => 'required|in:SAMO,PAMI,IOMA,CUSTOM',
            'formCodigo' => 'required|string|max:50',
            'formDescripcion' => 'required|string|max:1000',
            'formValor' => 'nullable|numeric|min:0',
        ]);

        $valorFinal = $this->formValor === '' ? null : (float) $this->formValor;

        if ($this->modoEdicion) {
            $practica = Nomenclador::find($this->practicaUlid);
            // Verificar que no le ponga un código que ya existe en otra práctica del mismo origen
            $existe = Nomenclador::where('origen', $this->formOrigen)
                ->where('codigo', $this->formCodigo)
                ->where('ulid', '!=', $this->practicaUlid)
                ->exists();

            if ($existe) {
                $this->addError('formCodigo', 'Este código ya existe para esta obra social.');
                return;
            }

            $practica->update([
                'origen' => $this->formOrigen,
                'codigo' => $this->formCodigo,
                'descripcion' => $this->formDescripcion,
                'valor' => $valorFinal,
                'activo' => $this->formActivo,
            ]);
            session()->flash('success', 'Práctica actualizada con éxito.');
        } else {
            // Alta
            $existe = Nomenclador::where('origen', $this->formOrigen)
                ->where('codigo', $this->formCodigo)
                ->exists();

            if ($existe) {
                $this->addError('formCodigo', 'Este código ya existe para esta obra social.');
                return;
            }

            Nomenclador::create([
                'origen' => $this->formOrigen,
                'codigo' => $this->formCodigo,
                'descripcion' => $this->formDescripcion,
                'valor' => $valorFinal,
                'activo' => $this->formActivo,
            ]);
            session()->flash('success', 'Práctica creada y agregada al nomenclador.');
        }

        $this->limpiarFormulario();
    }

    public function editarPractica($ulid)
    {
        $practica = Nomenclador::find($ulid);
        if ($practica) {
            $this->modoEdicion = true;
            $this->practicaUlid = $practica->ulid;
            $this->formOrigen = $practica->origen;
            $this->formCodigo = $practica->codigo;
            $this->formDescripcion = $practica->descripcion;
            $this->formValor = is_null($practica->valor) ? '' : $practica->valor;
            $this->formActivo = $practica->activo;
        }
    }

    public function limpiarFormulario()
    {
        $this->modoEdicion = false;
        $this->practicaUlid = null;
        $this->formOrigen = 'SAMO';
        $this->formCodigo = '';
        $this->formDescripcion = '';
        $this->formValor = '';
        $this->formActivo = true;
        $this->resetValidation();
    }

    public function toggleActivo($ulid)
    {
        $practica = Nomenclador::find($ulid);
        if ($practica) {
            $practica->update(['activo' => !$practica->activo]);
        }
    }

    public function eliminarPractica($ulid)
    {
        Nomenclador::where('ulid', $ulid)->delete();
        session()->flash('success', 'Práctica eliminada.');
    }

    public function with(): array
    {
        $query = Nomenclador::query();

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('codigo', 'like', '%' . $this->search . '%')
                    ->orWhere('descripcion', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->filtroOrigen)) {
            $query->where('origen', $this->filtroOrigen);
        }

        if ($this->filtroActivo !== '') {
            $query->where('activo', $this->filtroActivo);
        }

        return [
            'practicas' => $query->orderBy('origen')->orderBy('codigo')->paginate(15),
            'stats' => [
                'total' => Nomenclador::count(),
                'samo' => Nomenclador::where('origen', 'SAMO')->count(),
                'pami' => Nomenclador::where('origen', 'PAMI')->count(),
                'ioma' => Nomenclador::where('origen', 'IOMA')->count(),
            ]
        ];
    }
};
?>

<div>
    @if (session()->has('success')) <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r-xl shadow-sm flex justify-between"><span class="font-bold text-sm">{{ session('success') }}</span><button @click="show = false" class="text-green-500">✖</button></div> @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        <!-- PANEL DE CARGA MANUAL (Izquierda) -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 transition-all {{ $modoEdicion ? 'ring-2 ring-pba-cyan' : '' }}">
                <h3 class="font-pba font-extrabold text-lg text-pba-blue tracking-tight mb-4">
                    {{ $modoEdicion ? '✏️ Editar Práctica' : '➕ Cargar Nueva Práctica' }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-bold text-gray-500 uppercase block mb-1">Obra Social / Origen</label>
                        <select wire:model="formOrigen" class="w-full rounded-xl border-gray-200 text-sm focus:ring-pba-cyan">
                            <option value="SAMO">Ministerio (SAMO)</option>
                            <option value="PAMI">PAMI</option>
                            <option value="IOMA">IOMA</option>
                            <option value="CUSTOM">Otro / Personalizado</option>
                        </select>
                        @error('formOrigen') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-500 uppercase block mb-1">Código Oficial</label>
                        <input wire:model="formCodigo" type="text" placeholder="Ej: 420101" class="w-full rounded-xl border-gray-200 text-sm focus:ring-pba-cyan">
                        @error('formCodigo') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-500 uppercase block mb-1">Descripción</label>
                        <textarea wire:model="formDescripcion" rows="3" placeholder="Ej: Consulta Médica de Guardia..." class="w-full rounded-xl border-gray-200 text-sm focus:ring-pba-cyan"></textarea>
                        @error('formDescripcion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="text-[10px] font-bold text-gray-500 uppercase block mb-1">Valor ($)</label>
                            <input wire:model="formValor" type="number" step="0.01" placeholder="Vacio si es variable" class="w-full rounded-xl border-gray-200 text-sm focus:ring-pba-cyan">
                            @error('formValor') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-24">
                            <label class="text-[10px] font-bold text-gray-500 uppercase block mb-1">¿Activo?</label>
                            <button wire:click="$toggle('formActivo')" type="button" class="mt-1 relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $formActivo ? 'bg-pba-cyan' : 'bg-gray-200' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out {{ $formActivo ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-2">
                        <button wire:click="guardarPractica" class="flex-1 py-2 bg-gray-800 hover:bg-black text-white font-bold rounded-xl text-sm transition-colors shadow-md">
                            {{ $modoEdicion ? 'Actualizar' : 'Guardar' }}
                        </button>
                        @if($modoEdicion)
                            <button wire:click="limpiarFormulario" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-sm transition-colors">
                                Cancelar
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded-2xl shadow-inner p-4 flex justify-around text-center">
                <div><p class="text-xl font-pba font-bold text-pba-blue">{{ number_format($stats['samo'], 0, ',', '.') }}</p><p class="text-[9px] uppercase font-bold text-gray-500">SAMO</p></div>
                <div><p class="text-xl font-pba font-bold text-green-600">{{ number_format($stats['pami'], 0, ',', '.') }}</p><p class="text-[9px] uppercase font-bold text-gray-500">PAMI</p></div>
                <div><p class="text-xl font-pba font-bold text-purple-600">{{ number_format($stats['ioma'], 0, ',', '.') }}</p><p class="text-[9px] uppercase font-bold text-gray-500">IOMA</p></div>
            </div>
        </div>

        <!-- TABLA Y FILTROS (Derecha) -->
        <div class="lg:col-span-2 flex flex-col gap-4">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex flex-col sm:flex-row gap-4">
                <div class="relative w-full sm:flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por código o descripción..." class="pl-10 w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-pba-cyan text-sm">
                </div>
                <select wire:model.live="filtroOrigen" class="w-full sm:w-40 rounded-xl border-gray-200 bg-gray-50 focus:bg-white text-sm"><option value="">Cualquier Origen</option><option value="SAMO">SAMO</option><option value="PAMI">PAMI</option><option value="IOMA">IOMA</option><option value="CUSTOM">OTRO</option></select>
                <select wire:model.live="filtroActivo" class="w-full sm:w-32 rounded-xl border-gray-200 bg-gray-50 focus:bg-white text-sm"><option value="">Todas</option><option value="1">Activas</option><option value="0">Desactivadas</option></select>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex-1 flex flex-col">
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left border-collapse">
                        <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-200">
                            <th class="px-6 py-3 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Origen</th>
                            <th class="px-6 py-3 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Práctica</th>
                            <th class="px-6 py-3 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400 text-right">Valor</th>
                            <th class="px-6 py-3 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400 text-center">Estado</th>
                            <th class="px-6 py-3 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400 text-right">Acciones</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @forelse($practicas as $p)
                            <tr class="hover:bg-gray-50/50 transition-colors {{ !$p->activo ? 'opacity-50' : '' }}">
                                <td class="px-6 py-3"><span class="px-2 py-1 rounded text-[9px] font-bold uppercase tracking-wider @if($p->origen == 'SAMO') bg-pba-blue/10 text-pba-blue @elseif($p->origen == 'PAMI') bg-green-100 text-green-700 @elseif($p->origen == 'IOMA') bg-purple-100 text-purple-700 @else bg-gray-100 text-gray-600 @endif">{{ $p->origen }}</span></td>
                                <td class="px-6 py-3"><p class="font-mono text-sm font-bold text-gray-800">{{ $p->codigo }}</p><p class="text-xs text-gray-500 font-medium truncate w-64" title="{{ $p->descripcion }}">{{ $p->descripcion }}</p></td>
                                <td class="px-6 py-3 text-right">@if(is_null($p->valor)) <span class="text-xs text-gray-400 italic">Var.</span> @else <span class="font-bold text-gray-800">${{ number_format($p->valor, 2, ',', '.') }}</span> @endif</td>
                                <td class="px-6 py-3 text-center"><button wire:click="toggleActivo('{{ $p->ulid }}')" class="relative inline-flex h-4 w-7 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $p->activo ? 'bg-pba-cyan' : 'bg-gray-200' }}"><span class="pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow transition duration-200 ease-in-out {{ $p->activo ? 'translate-x-3' : 'translate-x-0' }}"></span></button></td>
                                <td class="px-6 py-3 text-right space-x-2">
                                    <button wire:click="editarPractica('{{ $p->ulid }}')" class="text-pba-blue hover:text-pba-cyan"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                    <button wire:click="eliminarPractica('{{ $p->ulid }}')" wire:confirm="¿Seguro que deseas eliminar esta práctica permanentemente?" class="text-red-400 hover:text-red-600"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500"><p class="font-bold text-gray-800">No hay prácticas</p></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($practicas->hasPages()) <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $practicas->links() }}</div> @endif
            </div>
        </div>
    </div>
</div>
