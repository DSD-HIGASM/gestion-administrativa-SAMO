<x-app-layout>
    <x-slot name="header">
        <h2 class="font-pba font-extrabold text-2xl text-pba-blue leading-tight uppercase tracking-tight">
            Panel de Configuración y Gestión
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

                <a href="{{ route('config.usuarios') }}" class="flex flex-col p-5 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md hover:border-pba-cyan/60 hover:-translate-y-1 transition-all duration-200 group text-center cursor-pointer">
                    <div class="w-12 h-12 mx-auto mb-4 rounded-xl bg-pba-cyan/10 flex items-center justify-center text-pba-cyan group-hover:bg-pba-cyan group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <h3 class="font-pba font-bold text-base text-pba-blue mb-2 group-hover:text-pba-cyan transition-colors leading-tight">Usuarios y accesos</h3>
                    <p class="font-sans text-xs text-gray-500 leading-snug">Altas, bajas, roles y permisos.</p>
                </a>

                <a href="{{ route('config.ingesta') }}" class="flex flex-col p-5 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md hover:border-pba-cyan/60 hover:-translate-y-1 transition-all duration-200 group text-center cursor-pointer">
                    <div class="w-12 h-12 mx-auto mb-4 rounded-xl bg-pba-cyan/10 flex items-center justify-center text-pba-cyan group-hover:bg-pba-cyan group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    </div>
                    <h3 class="font-pba font-bold text-base text-pba-blue mb-2 group-hover:text-pba-cyan transition-colors leading-tight">Ingreso de datos</h3>
                    <p class="font-sans text-xs text-gray-500 leading-snug">Importar archivos Excel HSI.</p>
                </a>

                <a href="{{ route('config.nomencladores') }}" class="flex flex-col p-5 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md hover:border-pba-cyan/60 hover:-translate-y-1 transition-all duration-200 group text-center cursor-pointer">
                    <div class="w-12 h-12 mx-auto mb-4 rounded-xl bg-pba-cyan/10 flex items-center justify-center text-pba-cyan group-hover:bg-pba-cyan group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    </div>
                    <h3 class="font-pba font-bold text-base text-pba-blue mb-2 group-hover:text-pba-cyan transition-colors leading-tight">Nomencladores</h3>
                    <p class="font-sans text-xs text-gray-500 leading-snug">Catálogo y actualización de valores.</p>
                </a>

                <a href="#" class="flex flex-col p-5 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md hover:border-pba-cyan/60 hover:-translate-y-1 transition-all duration-200 group text-center cursor-pointer">
                    <div class="w-12 h-12 mx-auto mb-4 rounded-xl bg-pba-cyan/10 flex items-center justify-center text-pba-cyan group-hover:bg-pba-cyan group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <h3 class="font-pba font-bold text-base text-pba-blue mb-2 group-hover:text-pba-cyan transition-colors leading-tight">Reglas de jefatura</h3>
                    <p class="font-sans text-xs text-gray-500 leading-snug">Diccionario HSI y exclusiones.</p>
                </a>

                <a href="#" class="flex flex-col p-5 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md hover:border-pba-cyan/60 hover:-translate-y-1 transition-all duration-200 group text-center cursor-pointer">
                    <div class="w-12 h-12 mx-auto mb-4 rounded-xl bg-pba-cyan/10 flex items-center justify-center text-pba-cyan group-hover:bg-pba-cyan group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <h3 class="font-pba font-bold text-base text-pba-blue mb-2 group-hover:text-pba-cyan transition-colors leading-tight">Sistema y desarrollo</h3>
                    <p class="font-sans text-xs text-gray-500 leading-snug">Variables, logs y mantenimiento.</p>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>
