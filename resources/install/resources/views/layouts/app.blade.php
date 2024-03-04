<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">
    <wireui:scripts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @rappasoftTableStyles
    @rappasoftTableThirdPartyStyles
</head>

<body>
    <div x-data="{ sidebarOpen: false }" class="flex h-screen bg-slate-200 font-roboto">
        @include('layouts.navigation')

        <div class="flex flex-col flex-1 overflow-hidden">
            @include('layouts.header')

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-200">
                <div class="container px-6 py-8 mx-auto">
                    @if (isset($header))
                        <h3 class="mb-4 text-3xl font-medium text-gray-700">
                            {{ $header }}
                        </h3>
                    @endif

                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
    @livewireScripts
    @livewire('wire-elements-modal')
    @stack('scripts')
    <x-livewire-alert::scripts />
    @rappasoftTableScripts
    @rappasoftTableThirdPartyScripts

</body>

</html>
