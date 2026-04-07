<x-guest-layout>
    <div class="mb-10 text-center">
        <h1 class="font-pba font-extrabold text-4xl text-pba-blue tracking-tight">SAMO</h1>
        <p class="font-sans font-bold text-xs text-pba-cyan uppercase tracking-widest mt-1">
            HIGA Gral. San Martín
        </p>
    </div>

    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <div>
            <label for="document" class="block font-sans font-bold text-[11px] text-pba-blue uppercase tracking-wider mb-2">
                Número de Documento
            </label>
            <input id="document"
                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:border-pba-cyan focus:ring focus:ring-pba-cyan focus:ring-opacity-20 transition duration-150 py-3 px-4 text-gray-700"
                   type="number"
                   name="document"
                   :value="old('document')"
                   required
                   autofocus
                   placeholder="Sin puntos ni espacios" />
            <x-input-error :messages="$errors->get('document')" class="mt-2 text-pba-magenta text-[11px] font-bold uppercase" />
        </div>

        <div>
            <label for="password" class="block font-sans font-bold text-[11px] text-pba-blue uppercase tracking-wider mb-2">
                Contraseña
            </label>
            <input id="password"
                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:border-pba-cyan focus:ring focus:ring-pba-cyan focus:ring-opacity-20 transition duration-150 py-3 px-4 text-gray-700"
                   type="password"
                   name="password"
                   required
                   placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-pba-magenta text-[11px] font-bold uppercase" />
        </div>

        <div class="flex items-center">
            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-pba-blue focus:ring-pba-cyan" name="remember">
            <span class="ms-2 text-sm text-gray-500 font-sans">Recordar mi sesión</span>
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full flex justify-center items-center px-4 py-4 bg-pba-blue hover:bg-pba-blue/90 active:bg-pba-blue border border-transparent rounded-lg font-pba font-extrabold text-white uppercase tracking-widest transition ease-in-out duration-150 shadow-lg shadow-pba-blue/20">
                Iniciar Sesión
            </button>
        </div>
    </form>
</x-guest-layout>
