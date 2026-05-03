<x-app-layout>
    <!-- Fondo Slate-100 para reducir el brillo general -->
    <div class="min-h-[calc(100vh-64px)] bg-slate-100 flex flex-col justify-center py-8 px-4">

        <div class="max-w-xl mx-auto w-full">
            <!-- Tarjeta más pequeña y sobria -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

                <!-- Línea de acento PBA delgada -->
                <div class="h-1 bg-pba-blue"></div>

                <div class="p-8 text-center">

                    <!-- Icono compacto -->
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-slate-50 text-pba-blue rounded-xl mb-6 border border-slate-100">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>

                    <div class="space-y-1 mb-8">
                        <h1 class="text-2xl font-pba font-extrabold text-slate-800 tracking-tight">
                            Portal SAMO
                        </h1>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-pba-cyan">
                            HIGA Gral. San Martín <span class="text-slate-300 px-1">|</span> La Plata
                        </p>
                    </div>

                    <div class="max-w-xs mx-auto border-t border-slate-50 pt-8">
                        <p class="text-slate-500 text-sm leading-relaxed font-medium">
                            Bienvenido al sistema. Use el menú superior para acceder a los módulos de su área.
                        </p>
                    </div>

                </div>
            </div>

            <!-- Footer Institucional con Créditos Solicitados -->
            <div class="mt-8 text-center">
                <div class="flex items-center justify-center gap-3 text-[9px] font-bold uppercase tracking-widest text-slate-400 mb-6">
                    <span class="bg-slate-200 px-2 py-0.5 rounded text-slate-500">v1.0 Beta</span>
                    <span class="opacity-30">|</span>
                    <span>{{ Auth::user()->name }}</span>
                    <span class="opacity-30">|</span>
                    <span>{{ now()->format('d/m/Y') }}</span>
                </div>

                <div class="pt-6 border-t border-slate-200">
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-[0.15em]">
                        Desarrollado por la <span class="text-slate-800">División de Salud Digital</span>
                    </p>
                    <p class="text-slate-400 text-[9px] font-medium uppercase mt-1">
                        HIGA General San Martín
                    </p>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
