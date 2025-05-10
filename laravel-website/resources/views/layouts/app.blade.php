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

        <div class="flex flex-col flex-1 p-6 pt-4 md:px-20 md:py-8 transition-all duration-300 ease-in-out ml-[4.5rem]"
            :class="sidebarOpen ? 'lg:ml-[14rem]' : ''">
            <!-- Navbar -->
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center">
                    <h2 class="text-2xl font-semibold leading-none">
                        @yield('title', 'Dashboard')
                    </h2>
                </div>

                <div id="account-wrapper" class="relative">
                    <button type="button" id="user-menu-button"
                        class="flex items-center gap-2 text-sm font-medium text-gray-800 bg-white border border-gray-200 px-2 py-2 focus:outline-none focus:bg-slate-100 rounded-lg ">

                        <!-- Inisial Profil -->
                        <span
                            class="w-7 h-7 rounded-md bg-customBlue text-white flex items-center justify-center text-xs font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>

                        <span>{{ auth()->user()->name }}</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>

                    <div id="user-dropdown"
                        class="hidden absolute right-0 mt-2 w-48 bg-customBlue border rounded-lg shadow-lg z-50 transition-opacity duration-200 ease-in-out">
                        <ul class="py-2 text-sm text-slate-700">
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="block">
                                    @csrf
                                    <button type="submit"
                                        class="w-full px-4 py-2 text-left text-white hover:bg-customBlue-hover flex items-center gap-2">
                                        <i class="fas fa-sign-out-alt"></i>
                                        Sign out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

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
