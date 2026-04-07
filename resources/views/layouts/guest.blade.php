<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SAMO') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased bg-gray-50">
<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
    <div class="w-full sm:max-w-md bg-white shadow-2xl overflow-hidden sm:rounded-xl border border-gray-200">
        <div class="h-1.5 w-full flex">
            <div class="h-full w-1/3 bg-pba-magenta"></div>
            <div class="h-full w-1/3 bg-pba-blue"></div>
            <div class="h-full w-1/3 bg-pba-cyan"></div>
        </div>
        <div class="px-10 py-12">
            {{ $slot }}
        </div>
    </div>
    <div class="mt-8 text-center">
        <p class="text-[10px] font-pba text-gray-400 uppercase tracking-[0.2em]">
            Ministerio de Salud — Gobierno de la Provincia de Buenos Aires
        </p>
    </div>
</div>
</body>
</html>
