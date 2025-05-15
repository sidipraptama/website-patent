<!DOCTYPE html>
<html lang="en" x-data="{
    sidebarOpen: false,
    initSidebar() {
        this.sidebarOpen = localStorage.getItem('sidebarOpen') === 'true';
    },
    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
        localStorage.setItem('sidebarOpen', this.sidebarOpen);
    }
}" x-init="initSidebar()">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard')</title>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white text-gray-900 custom-background" x-init="initSidebar()">
    <style>
        .custom-background::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-image: url('{{ asset('images/background.svg') }}');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.12;
            z-index: -10;
            pointer-events: none;
            mix-blend-mode: ;
        }
    </style>
    <div class="flex-1">
        <!-- Sidebar -->
        @include('layouts.sidebar')

        <div class="flex flex-col flex-1 ml-[4.5rem] overflow-auto h-screen transition-all duration-300"
            :class="sidebarOpen ? 'lg:ml-[14rem]' : ''">

            <!-- Konten -->
            <main class="flex-1">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const userMenuButton = document.getElementById("user-menu-button");
            const userDropdown = document.getElementById("user-dropdown");
            const accountWrapper = document.getElementById("account-wrapper");
            let isDropdownOpen = false;
            let hideTimeout;

            // Fungsi untuk menampilkan dropdown
            function showDropdown() {
                clearTimeout(hideTimeout);
                userDropdown.classList.remove("hidden");
            }

            // Fungsi untuk menyembunyikan dropdown
            function hideDropdown() {
                if (!isDropdownOpen) {
                    hideTimeout = setTimeout(() => {
                        userDropdown.classList.add("hidden");
                    }, 200);
                }
            }

            // Event saat hover masuk (selalu tampilkan dropdown)
            accountWrapper.addEventListener("mouseenter", showDropdown);

            // Event saat hover keluar (hanya hilangkan jika belum diklik)
            accountWrapper.addEventListener("mouseleave", hideDropdown);

            // Event saat tombol diklik
            userMenuButton.addEventListener("click", function(event) {
                event.stopPropagation(); // Mencegah event klik menutup dropdown langsung
                isDropdownOpen = !isDropdownOpen;
                if (isDropdownOpen) {
                    showDropdown();
                } else {
                    userDropdown.classList.add("hidden");
                }
            });

            // Tutup dropdown hanya jika klik di luar area menu
            document.addEventListener("click", function(event) {
                if (!accountWrapper.contains(event.target)) {
                    isDropdownOpen = false;
                    userDropdown.classList.add("hidden");
                }
            });
        });
    </script>

    @yield('scripts')
</body>

</html>
