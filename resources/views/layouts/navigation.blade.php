<nav x-data="{ open: false }" class="bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex">

                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-4 group">
                        <div class="w-12 h-12 rounded-full border border-gray-200 flex items-center justify-center bg-gray-50 group-hover:border-pba-cyan transition duration-200">
                            <span class="font-pba font-extrabold text-pba-blue text-sm">PBA</span>
                        </div>
                        <div class="hidden sm:block">
                            <div class="font-pba font-extrabold text-xl text-pba-blue tracking-tight leading-none">SAMO</div>
                            <div class="font-sans font-bold text-[10px] text-pba-cyan uppercase tracking-widest mt-1">Gral. San Martín</div>
                        </div>
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-12 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="font-sans font-bold text-[12px] uppercase tracking-wider text-gray-500 hover:text-pba-blue hover:border-pba-cyan focus:text-pba-blue transition">
                        Inicio
                    </x-nav-link>

                    <x-nav-link href="#" class="font-sans font-bold text-[12px] uppercase tracking-wider text-gray-500 hover:text-pba-blue hover:border-pba-cyan focus:text-pba-blue transition">
                        Guardia
                    </x-nav-link>

                    <x-nav-link href="#" class="font-sans font-bold text-[12px] uppercase tracking-wider text-gray-500 hover:text-pba-blue hover:border-pba-cyan focus:text-pba-blue transition">
                        Ambulatorio
                    </x-nav-link>

                    <x-nav-link href="#" class="font-sans font-bold text-[12px] uppercase tracking-wider text-gray-500 hover:text-pba-blue hover:border-pba-cyan focus:text-pba-blue transition">
                        Nomencladores
                    </x-nav-link>

                    <x-nav-link :href="route('config.index')" class="font-sans font-bold text-[12px] uppercase tracking-wider text-gray-500 hover:text-pba-blue hover:border-pba-cyan focus:text-pba-blue transition">
                        Configuraciónes
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-sans font-bold rounded-md text-pba-blue bg-white hover:text-pba-cyan focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name ?? 'Operador' }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                             onclick="event.preventDefault(); this.closest('form').submit();"
                                             class="font-sans text-sm font-bold text-pba-magenta">
                                Cerrar Sesión
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-gray-200">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="font-sans font-bold text-pba-blue border-pba-cyan">
                Inicio
            </x-responsive-nav-link>
            <x-responsive-nav-link href="#" class="font-sans font-bold text-gray-600">
                Guardia
            </x-responsive-nav-link>
            <x-responsive-nav-link href="#" class="font-sans font-bold text-gray-600">
                Ambulatorio
            </x-responsive-nav-link>
            <x-responsive-nav-link href="#" class="font-sans font-bold text-gray-600">
                Nomencladores
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('config.index')" class="font-sans font-bold text-gray-600">
                Configuraciónes
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200 bg-gray-50">
            <div class="px-4">
                <div class="font-sans font-bold text-base text-pba-blue">{{ Auth::user()->name ?? 'Operador' }}</div>
                <div class="font-sans font-medium text-xs text-gray-500">Documento: {{ Auth::user()->document ?? '' }}</div>
            </div>

            <div class="mt-3 space-y-1">

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                                           onclick="event.preventDefault(); this.closest('form').submit();"
                                           class="font-sans font-bold text-pba-magenta">
                        Cerrar Sesión
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
