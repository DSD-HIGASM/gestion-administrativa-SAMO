<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SAMO - Gestión') }}</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Encode+Sans:wght@600;800&family=Roboto:wght@300;400;700&display=swap');
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">
<div class="min-h-screen flex flex-col">

    <div class="h-1.5 w-full flex z-50">
        <div class="h-full w-1/3 bg-pba-magenta"></div>
        <div class="h-full w-1/3 bg-pba-blue"></div>
        <div class="h-full w-1/3 bg-pba-cyan"></div>
    </div>

    @include('layouts.navigation')

    @isset($header)
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endisset

    <main class="flex-grow">
        {{ $slot }}
    </main>

    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center text-[11px] text-gray-500 font-sans uppercase tracking-widest gap-4">
            <div>
                <span class="font-bold text-pba-blue">SAMO</span> — HIGA Gral. San Martín
            </div>
            <div class="text-center sm:text-right">
                Ministerio de Salud<br>Gobierno de la Provincia de Buenos Aires
            </div>
        </div>
    </footer>
</div>
</body>
</html>
