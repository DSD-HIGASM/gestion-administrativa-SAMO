<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('samo.guardia.index') }}" class="text-gray-400 hover:text-pba-blue transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="font-pba font-extrabold text-2xl text-pba-blue leading-tight uppercase tracking-tight">
                    Expediente SAMO: <span class="text-pba-cyan">{{ $tramite->codigo_visual }}</span>
                </h2>
            </div>
            <div>
                <span class="px-3 py-1 text-xs font-bold rounded-full text-white uppercase tracking-wider shadow-sm" style="background-color: {{ $tramite->estado->color_hex ?? '#000' }}">
                    {{ $tramite->estado->nombre }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:expediente-guardia :tramite="$tramite" />
        </div>
    </div>
</x-app-layout>
