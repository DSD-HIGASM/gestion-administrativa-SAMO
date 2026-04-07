<?php

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule; // Importación necesaria para la regla unique

new class extends Component
{
    public $mostrarModal = false;
    public $modoEdicion = false;

    public $usuarioUlid;
    public $name;
    public $lastname;
    public $document;
    public $is_active = true;

    public $selectedRoles = [];

    public function abrirModalCreacion()
    {
        $this->reset(['usuarioUlid', 'name', 'lastname', 'document', 'selectedRoles']);
        $this->resetValidation();

        $this->modoEdicion = false;
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalEdicion($ulid)
    {
        $this->resetValidation();
        $this->modoEdicion = true;

        $user = User::withTrashed()->where('ulid', $ulid)->firstOrFail();

        $this->usuarioUlid = $user->ulid;
        $this->name = $user->name;
        $this->lastname = $user->lastname;
        $this->document = $user->document;
        $this->is_active = !$user->trashed();

        $this->selectedRoles = $user->roles->pluck('name')->toArray();

        $this->mostrarModal = true;
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->reset(['usuarioUlid', 'name', 'lastname', 'document', 'is_active', 'selectedRoles', 'modoEdicion']);
        $this->resetValidation();
    }

    public function toggleRole($roleName)
    {
        if (in_array($roleName, $this->selectedRoles)) {
            $this->selectedRoles = array_diff($this->selectedRoles, [$roleName]);
        } else {
            $this->selectedRoles[] = $roleName;
        }
    }

    public function guardarUsuario()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'document' => [
                'required',
                'integer',
                'max:99999999',
                // Corrección: Le decimos explícitamente a Laravel que use 'ulid' para ignorar el registro
                Rule::unique('users', 'document')->ignore($this->modoEdicion ? $this->usuarioUlid : null, 'ulid')
            ],
            'selectedRoles' => 'array',
            'selectedRoles.*' => 'string|exists:roles,name',
        ]);

        $isCurrentUserDev = auth()->user()->hasRole('dev');

        if ($this->modoEdicion) {
            $user = User::withTrashed()->where('ulid', $this->usuarioUlid)->firstOrFail();
            $isTargetDev = $user->hasRole('dev');

            if ($isTargetDev && !$isCurrentUserDev) {
                $this->is_active = !$user->trashed();

                if (!in_array('dev', $this->selectedRoles)) {
                    $this->selectedRoles[] = 'dev';
                }
            }

            $user->update([
                'name' => $this->name,
                'lastname' => $this->lastname,
                'document' => $this->document,
            ]);

            $user->syncRoles($this->selectedRoles);

            if ($this->is_active && $user->trashed()) {
                $user->restore();
            } elseif (!$this->is_active && !$user->trashed()) {
                $user->delete();
            }

        } else {
            if (!$isCurrentUserDev && in_array('dev', $this->selectedRoles)) {
                $this->selectedRoles = array_diff($this->selectedRoles, ['dev']);
            }

            $user = User::create([
                'ulid' => strtolower((string) Str::ulid()),
                'name' => $this->name,
                'lastname' => $this->lastname,
                'document' => $this->document,
                'password' => Hash::make($this->document),
            ]);

            $user->syncRoles($this->selectedRoles);

            if (!$this->is_active) {
                $user->delete();
            }
        }

        $this->cerrarModal();
    }

    public function resetearContrasena()
    {
        $user = User::withTrashed()->where('ulid', $this->usuarioUlid)->firstOrFail();

        if ($user->hasRole('dev') && !auth()->user()->hasRole('dev')) {
            abort(403, 'No tienes permisos para resetear la contraseña de un desarrollador.');
        }

        $user->update([
            'password' => Hash::make($user->document)
        ]);

        $this->cerrarModal();
    }

    public function with(): array
    {
        $query = Role::query();
        if (!auth()->user()->hasRole('dev')) {
            $query->where('name', '!=', 'dev');
        }

        return [
            'users' => User::withTrashed()->with('roles')->get(),
            'rolesDisponibles' => $query->get()
        ];
    }
};
?>

<div>
    @php
        $dev_edit = in_array('dev', $selectedRoles) && !auth()->user()->hasRole('dev');
    @endphp

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white flex items-center justify-between">
            <div>
                <h3 class="font-pba font-extrabold text-xl text-pba-blue tracking-tight">Directorio de Personal</h3>
                <p class="font-sans text-xs text-gray-500 mt-1">Gestión de accesos, roles y estado de las cuentas.</p>
            </div>

            <button
                wire:click="abrirModalCreacion"
                class="px-5 py-2.5 bg-pba-blue text-white rounded-xl text-sm font-pba font-bold shadow-md hover:bg-pba-cyan hover:shadow-lg transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nuevo Usuario
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400 whitespace-nowrap">ID Sistema</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Usuario</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Documento</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Estado</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400">Roles Asignados</th>
                    <th class="px-6 py-4 font-sans font-bold text-[10px] uppercase tracking-widest text-gray-400 text-right">Acciones</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                @foreach ($users as $user)
                    <tr class="hover:bg-gray-50/80 transition-colors group {{ $user->trashed() ? 'bg-gray-50/50' : '' }}">

                        <td class="px-6 py-4 font-mono text-[11px] {{ $user->trashed() ? 'text-gray-300' : 'text-gray-400' }}">
                            {{ substr($user->ulid, 0, 8) }}...
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-pba-cyan/10 flex items-center justify-center text-pba-cyan font-pba font-bold text-xs shrink-0 {{ $user->trashed() ? 'opacity-50' : '' }}">
                                    {{ substr($user->name, 0, 1) }}{{ substr($user->lastname, 0, 1) }}
                                </div>
                                <div class="{{ $user->trashed() ? 'opacity-60' : '' }}">
                                    <p class="font-sans text-sm font-bold text-gray-800 leading-tight">{{ $user->lastname }}, {{ $user->name }}</p>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 font-sans text-sm font-medium {{ $user->trashed() ? 'text-gray-400' : 'text-gray-600' }}">
                            {{ $user->document }}
                        </td>

                        <td class="px-6 py-4">
                            @if(!$user->trashed())
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold bg-green-50 text-green-700 border border-green-200 uppercase tracking-wider">
                                        <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div> Activo
                                    </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold bg-red-50 text-red-700 border border-red-100 uppercase tracking-wider">
                                        <div class="w-1.5 h-1.5 rounded-full bg-red-400"></div> Inactivo
                                    </span>
                            @endif
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1.5 {{ $user->trashed() ? 'opacity-60' : '' }}">
                                @forelse($user->roles as $rol)
                                    <span class="px-2 py-0.5 bg-pba-blue/5 border border-pba-blue/10 text-pba-blue text-[10px] font-bold rounded uppercase tracking-wider">
                                            {{ $rol->name }}
                                        </span>
                                @empty
                                    <span class="text-[11px] text-gray-400 italic">Sin roles</span>
                                @endforelse
                            </div>
                        </td>

                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <button
                                type="button"
                                wire:click="abrirModalEdicion('{{ $user->ulid }}')"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-pba-cyan hover:bg-pba-cyan/10 transition-all focus:outline-none focus:ring-2 focus:ring-pba-cyan/30">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($mostrarModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" wire:click="cerrarModal"></div>

            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-gray-100">

                    <div class="bg-white border-b border-gray-100 px-6 py-5 flex items-center justify-between sticky top-0 z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-pba-cyan/10 text-pba-cyan flex items-center justify-center">
                                @if($modoEdicion)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="font-pba font-bold text-xl text-pba-blue leading-tight" id="modal-title">
                                    {{ $modoEdicion ? 'Gestión de Perfil' : 'Nuevo Usuario' }}
                                </h3>
                                @if($modoEdicion)
                                    <p class="font-mono text-[10px] text-gray-400 tracking-wider">ID: {{ $usuarioUlid }}</p>
                                @else
                                    <p class="font-mono text-[10px] text-gray-400 tracking-wider">Complete los datos para registrar un acceso.</p>
                                @endif
                            </div>
                        </div>
                        <button type="button" wire:click="cerrarModal" class="text-gray-400 hover:text-gray-700 hover:bg-gray-100 p-2 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <form wire:submit="guardarUsuario">
                        <div class="px-6 py-6 space-y-8 max-h-[70vh] overflow-y-auto">

                            <section>
                                <h4 class="font-sans text-[11px] font-extrabold text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                                    Información Básica
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div class="col-span-1 md:col-span-1">
                                        <label for="document" class="block font-sans text-[11px] font-bold text-gray-700 uppercase tracking-wide mb-1.5">Documento</label>
                                        <input type="text" id="document" wire:model="document" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-pba-cyan focus:ring focus:ring-pba-cyan/20 font-sans text-sm text-gray-800 transition-colors">
                                        @error('document') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-span-1 md:col-span-1">
                                        <label for="name" class="block font-sans text-[11px] font-bold text-gray-700 uppercase tracking-wide mb-1.5">Nombres</label>
                                        <input type="text" id="name" wire:model="name" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-pba-cyan focus:ring focus:ring-pba-cyan/20 font-sans text-sm text-gray-800 transition-colors">
                                        @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-span-1 md:col-span-1">
                                        <label for="lastname" class="block font-sans text-[11px] font-bold text-gray-700 uppercase tracking-wide mb-1.5">Apellidos</label>
                                        <input type="text" id="lastname" wire:model="lastname" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-pba-cyan focus:ring focus:ring-pba-cyan/20 font-sans text-sm text-gray-800 transition-colors">
                                        @error('lastname') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </section>

                            <section>
                                <h4 class="font-sans text-[11px] font-extrabold text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                    Asignación de Roles
                                </h4>
                                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                    <p class="font-sans text-xs text-gray-500 mb-3">Selecciona uno o más roles para este usuario haciendo clic en las opciones.</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($rolesDisponibles as $rolDisponible)
                                            <button
                                                type="button"
                                                wire:click="toggleRole('{{ $rolDisponible->name }}')"
                                                class="px-4 py-2 text-xs font-bold rounded-lg transition-all border shadow-sm flex items-center gap-2
                                                {{ in_array($rolDisponible->name, $selectedRoles)
                                                    ? 'bg-pba-cyan text-white border-pba-cyan scale-105'
                                                    : 'bg-white text-gray-500 border-gray-200 hover:border-pba-cyan hover:text-pba-cyan' }}">

                                                @if(in_array($rolDisponible->name, $selectedRoles))
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                                @endif
                                                {{ strtoupper($rolDisponible->name) }}
                                            </button>
                                        @endforeach
                                    </div>
                                    @error('selectedRoles') <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span> @enderror
                                </div>
                            </section>

                            <section>
                                <h4 class="font-sans text-[11px] font-extrabold text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                    Seguridad y Acceso
                                </h4>

                                @if(!$modoEdicion)
                                    <div class="bg-pba-blue/5 border border-pba-blue/20 rounded-xl p-4 flex items-start gap-3">
                                        <svg class="w-5 h-5 text-pba-blue shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <div>
                                            <p class="font-sans font-bold text-sm text-pba-blue">Contraseña predeterminada</p>
                                            <p class="font-sans text-xs text-gray-600 mt-1">Al crear el usuario, la contraseña inicial será automáticamente su número de documento. Luego el sistema le pedirá cambiarla en su primer inicio de sesión.</p>
                                        </div>
                                    </div>
                                @endif

                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 {{ !$modoEdicion ? 'mt-4' : '' }}">

                                    <div class="flex-1 bg-white border {{ $dev_edit ? 'border-gray-100 bg-gray-50/50' : 'border-gray-200' }} rounded-xl p-4 flex items-center justify-between shadow-sm transition-all">
                                        <div>
                                            <p class="font-sans font-bold text-sm text-gray-800">Estado de la cuenta</p>
                                            <p class="font-sans text-[11px] text-gray-500">
                                                @if($dev_edit)
                                                    <span class="text-orange-500 font-medium">Bloqueado:</span> No se puede suspender a un rol Dev.
                                                @else
                                                    {{ $is_active ? 'El usuario puede ingresar al sistema.' : 'El acceso está suspendido.' }}
                                                @endif
                                            </p>
                                        </div>
                                        <label class="relative inline-flex items-center {{ $dev_edit ? 'cursor-not-allowed opacity-60' : 'cursor-pointer' }}">
                                            <input
                                                type="checkbox"
                                                wire:model="is_active"
                                                class="sr-only peer"
                                                {{ $dev_edit ? 'disabled' : '' }}
                                            >
                                            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-pba-cyan/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[6px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-green-500"></div>
                                        </label>
                                    </div>

                                    @if($modoEdicion)
                                        <div class="flex-1 bg-white border {{ $dev_edit ? 'border-gray-100 bg-gray-50/50' : 'border-gray-200' }} rounded-xl p-4 flex items-center justify-between shadow-sm transition-all">
                                            <div>
                                                <p class="font-sans font-bold text-sm text-gray-800">Contraseña</p>
                                                <p class="font-sans text-[11px] text-gray-500">
                                                    @if($dev_edit)
                                                        <span class="text-orange-500 font-medium">Bloqueado:</span> No se puede resetear clave a un rol Dev.
                                                    @else
                                                        Restablecer al documento.
                                                    @endif
                                                </p>
                                            </div>
                                            <button
                                                type="button"
                                                wire:click="resetearContrasena"
                                                wire:confirm="¿Estás seguro de que deseas restablecer la contraseña de este usuario? El nuevo password será su número de documento."
                                                class="p-2 border rounded-lg transition-colors {{ $dev_edit ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed opacity-60' : 'bg-gray-50 text-gray-600 border-gray-200 hover:text-pba-magenta hover:bg-pba-magenta/10 hover:border-pba-magenta/30' }}"
                                                title="{{ $dev_edit ? 'Acción no permitida' : 'Restablecer Contraseña' }}"
                                                {{ $dev_edit ? 'disabled' : '' }}>
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                            </button>
                                        </div>
                                    @endif

                                </div>
                            </section>

                        </div>

                        <div class="bg-gray-50 border-t border-gray-100 px-6 py-4 flex items-center justify-end gap-3 rounded-b-2xl">
                            <button type="button" wire:click="cerrarModal" class="px-5 py-2.5 font-sans text-sm font-bold text-gray-500 hover:text-gray-800 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" class="px-6 py-2.5 bg-pba-blue hover:bg-pba-cyan text-white font-sans text-sm font-bold rounded-xl shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                                @if($modoEdicion)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Guardar Cambios
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Crear Registro
                                @endif
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
