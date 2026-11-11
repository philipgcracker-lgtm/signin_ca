<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' | ' : '' }}{{ config('app.name', 'ScanIn') }}</title>
        <meta name="robots" content="noindex">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-cream">
            <div>
                <a href="/" class="font-heading font-medium text-2xl tracking-[.2em] text-ink">SCAN<span class="text-sage">IN</span></a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white border border-line shadow-sm overflow-hidden sm:rounded-xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
