<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Exclusion;

new class extends Component {
    use WithPagination;

    public $formTipo = 'Servicio Guardia';
    public $formValor = '';
    public $formMotivo = '';

    public $search = '';

    public function updatingSearch() { $this->resetPage(); }

    public function guardarRegla()
    {
        $this->validate([
            'formTipo' => 'required|string',
            'formValor' => 'required|string|min:2|max:255',
        ]);

        $existe = Exclusion::where('tipo_exclusion', $this->formTipo)
            ->where('valor_exacto', $this->formValor)
            ->exists();

        if ($existe) {
            $this->addError('formValor', 'Esta regla de exclusión ya existe.');
            return;
        }

        Exclusion::create([
            'tipo_exclusion' => $this->formTipo,
            'valor_exacto' => $this->formValor,
            'motivo_exclusion' => $this->formMotivo,
        ]);

        $this->reset(['formValor', 'formMotivo']);
        session()->flash('success', 'Regla de exclusión creada. Las atenciones futuras que coincidan se ocultarán de la bandeja principal.');
    }

    public function toggleActiva($ulid)
    {
        $regla = Exclusion::find($ulid);
        if ($regla) {
            $regla->update(['activa' => !$regla->activa]);
        }
    }

    public function eliminarRegla($ulid)
    {
        Exclusion::where('ulid', $ulid)->delete();
    }

    public function with(): array
    {
        $query = Exclusion::query();

        if (!empty($this->search)) {
            $query->where('valor_exacto', 'like', '%' . $this->search . '%')
                ->orWhere('tipo_exclusion', 'like', '%' . $this->search . '%');
        }

        return [
            'reglas' => $query->orderBy('tipo_exclusion')->orderBy('valor_exacto')->paginate(15)
        ];
    }
};
?>

<div>
    @if (session()->has('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r-xl shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-3"><span class="font-bold text-sm">{{ session('success') }}</span></div>
            <button @click="show = false" class="text-green-500">✖</button>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- FORMULARIO DE ALTA -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
                <h3 class="font-pba font-extrabold text-lg text-pba-blue tracking-tight mb-2">Crear Regla de Ocultamiento</h3>
                <p class="text-xs text-gray-500 mb-6">Define qué servicios o profesionales deben ir a la bandeja de "Revisión Secundaria" automáticamente al migrar desde HSI.</p>

                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-bold text-gray-500 uppercase block mb-1">¿Dónde se aplica?</label>
                        <select wire:model="formTipo" class="w-full rounded-xl border-gray-200 text-sm focus:ring-pba-cyan">
                            <option value="Servicio Guardia">Servicio de Guardia</option>
                            <option value="Servicio Ambulatorio">Servicio Ambulatorio</option>
                            <option value="Profesional">Nombre del Profesional</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-500 uppercase block mb-1">Valor Exacto (Como viene de HSI)</label>
                        <input wire:model="formValor" type="text" placeholder="Ej: Enfermería, Oftalmología..." class="w-full rounded-xl border-gray-200 text-sm focus:ring-pba-cyan">
                        @error('formValor') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-500 uppercase block mb-1">Motivo (Opcional)</label>
                        <input wire:model="formMotivo" type="text" placeholder="Ej: No es facturable en SAMO" class="w-full rounded-xl border-gray-200 text-sm focus:ring-pba-cyan">
                    </div>

                    <button wire:click="guardarRegla" class="w-full py-3 bg-gray-800 hover:bg-black text-white font-bold rounded-xl shadow-md transition-all">
                        Añadir Regla
                    </button>
                </div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                <p class="text-xs text-yellow-800 font-medium">💡 <strong>Nota:</strong> Las reglas no borran los datos, simplemente los marcan en la base de datos para que las bandejas principales no se saturen.</p>
            </div>
        </div>

        <!-- TABLA DE REGLAS -->
        <div class="lg:col-span-2 flex flex-col gap-4">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar regla..." class="pl-10 w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-pba-cyan text-sm">
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex-1 flex flex-col">
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left border-collapse">
                        <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-200">
                            <th class="px-6 py-3 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Tipo de Regla</th>
                            <th class="px-6 py-3 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Valor a Ocultar</th>
                            <th class="px-6 py-3 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400 text-center">Activa</th>
                            <th class="px-6 py-3 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400 text-right">Acción</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @forelse($reglas as $regla)
                            <tr class="hover:bg-gray-50/50 transition-colors {{ !$regla->activa ? 'opacity-50' : '' }}">
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-[9px] font-bold uppercase tracking-wider bg-gray-100 text-gray-600">{{ $regla->tipo_exclusion }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-gray-800 text-sm">{{ $regla->valor_exacto }}</p>
                                    <p class="text-[10px] text-gray-500 uppercase">{{ $regla->motivo_exclusion }}</p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button wire:click="toggleActiva('{{ $regla->ulid }}')" class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $regla->activa ? 'bg-pba-cyan' : 'bg-gray-200' }}">
                                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200 ease-in-out {{ $regla->activa ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button wire:click="eliminarRegla('{{ $regla->ulid }}')" wire:confirm="¿Seguro que deseas eliminar esta regla?" class="text-red-400 hover:text-red-600">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <p class="font-bold text-gray-800">No hay reglas definidas.</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($reglas->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                        {{ $reglas->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
