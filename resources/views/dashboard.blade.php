<x-app-layout>
    <x-slot name="header">
        <h2 class="font-pba font-extrabold text-2xl text-pba-blue leading-tight uppercase tracking-tight">
            Panel de Control
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl shadow-gray-200/50 sm:rounded-2xl border border-gray-100">
                <div class="p-10">

                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 mb-10 pb-8 border-b border-gray-100">
                        <div class="w-16 h-16 bg-pba-cyan/10 rounded-2xl flex items-center justify-center text-pba-cyan shadow-inner">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-pba font-bold text-2xl text-pba-blue tracking-tight">Bienvenido, {{ Auth::user()->name ?? 'Operador' }}</h3>
                            <p class="font-sans text-sm text-gray-500 mt-1">Resumen general de atenciones pendientes de facturación en el sistema SAMO.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                        <div class="border border-gray-100 bg-gray-50 rounded-xl p-6 hover:shadow-lg hover:border-pba-cyan/30 transition duration-200">
                            <div class="font-sans font-bold text-[11px] text-gray-500 uppercase tracking-widest mb-3">Guardia (HSI)</div>
                            <div class="font-pba font-extrabold text-4xl text-pba-magenta">0</div>
                            <div class="font-sans font-medium text-xs text-gray-400 mt-3 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-pba-magenta"></span> Atenciones sin mapear
                            </div>
                        </div>

                        <div class="border border-gray-100 bg-gray-50 rounded-xl p-6 hover:shadow-lg hover:border-pba-cyan/30 transition duration-200">
                            <div class="font-sans font-bold text-[11px] text-gray-500 uppercase tracking-widest mb-3">Consultorios Externos</div>
                            <div class="font-pba font-extrabold text-4xl text-pba-magenta">0</div>
                            <div class="font-sans font-medium text-xs text-gray-400 mt-3 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-pba-magenta"></span> Atenciones sin mapear
                            </div>
                        </div>

                        <div class="border border-gray-100 bg-gray-50 rounded-xl p-6 hover:shadow-lg hover:border-pba-cyan/30 transition duration-200">
                            <div class="font-sans font-bold text-[11px] text-gray-500 uppercase tracking-widest mb-3">Nomencladores</div>
                            <div class="font-pba font-extrabold text-4xl text-pba-cyan">0</div>
                            <div class="font-sans font-medium text-xs text-gray-400 mt-3 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-pba-cyan"></span> Prácticas valorizadas activas
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
