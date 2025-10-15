<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <link rel="icon" href="{{ asset('images/title.ico') }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="author" content="LEFT4CODE">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Quality Page | BE INVENTORY' }}</title>
        <!-- BEGIN: CSS Assets-->
        <link rel="stylesheet" href="{{ asset('dist/css/app.css') }}" />
        {{-- @vite(['resources/assets/css/app.css',
                'resources/assets/js/app.js',]) --}}

        <!-- di <head> atau sebelum </body> -->
        <!-- SweetAlert2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <style>
            * {
                font-family: 'Lexend Deca', sans-serif !important;
            }
        </style>

        <!-- SweetAlert2 JS -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- END: CSS Assets-->
        @livewireStyles
    </head>

    <body class="app">
        <!-- BEGIN: Mobile Menu -->
        <x-app.mobile-menuquaility/>
        <!-- END: Mobile Menu -->
        <div class="flex">
            <!-- BEGIN: Side Menu -->
            <x-app.sidebarqualitypage/>

            <!-- END: Side Menu -->
            <!-- BEGIN: Content -->
            <div class="content">
                <!-- BEGIN: Top Bar -->
                <x-app.headerqualitypage/>

                {{ $slot }} {{-- This is where your page content will go --}}
            </div>
        </div>
        <!-- BEGIN: JS Assets-->
        <script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script>
        <script src="https://maps.googleapis.com/maps/api/js?key=["your-google-map-api"]&libraries=places"></script>
        <script src="{{ asset('dist/js/app.js') }}"></script>
        <script src="https://unpkg.com/feather-icons"></script>

        <!-- END: JS Assets-->
        @livewireScripts
        {{-- <script src="//unpkg.com/alpinejs" defer></script> --}}
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                Livewire.on('qcError', (data) => {
                    console.error('Event error diterima:', data);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message || 'Terjadi kesalahan',
                        showConfirmButton: true
                    });
                });
            });
        </script>
    </body>
</html>
