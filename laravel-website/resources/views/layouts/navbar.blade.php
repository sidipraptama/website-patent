<nav class="fixed top-0 left-0 right-0 w-full bg-white shadow-md p-2 flex items-center h-[3.5rem] z-50">
    <!-- Logo & Toggle Sidebar -->
    <div class="px-3 py-2 rounded-lg hover:bg-blue-200 transition cursor-pointer" @click="toggleSidebar()">
        <span class="text-slate-800 text-lg font-bold select-none">Paten.ai</span>
    </div>

    <!-- Teks Dinamis di Tengah -->
    <div class="flex-1 text-center">
        <span class="text-blue-800 text-lg font-semibold">@yield('title', 'Dashboard')</span>
    </div>

    <!-- Account Dropdown -->
    <div id="account-wrapper" class="relative p-3">
        <button type="button" id="user-menu-button"
            class="flex items-center gap-2 text-sm font-medium text-blue-700 bg-white px-4 py-2 hover:bg-blue-100 focus:outline-none focus:bg-blue-100 transition">
            <span>{{ auth()->user()->name }}</span>
            <!-- Chevron Down Icon -->
            <i class="fas fa-chevron-down text-xs text-blue-500"></i>
        </button>

        <!-- Dropdown menu -->
        <div id="user-dropdown"
            class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-50 transition-opacity duration-200 ease-in-out">
            <ul class="py-2 text-sm text-blue-700">
                <li>
                    <form action="{{ route('logout') }}" method="POST" class="block">
                        @csrf
                        @method('POST')
                        <button type="submit"
                            class="w-full px-4 py-2 text-left text-red-500 hover:bg-blue-100 flex items-center gap-2">
                            <i class="fas fa-sign-out-alt"></i>
                            Sign out
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- JavaScript -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const userMenuButton = document.getElementById("user-menu-button");
        const userDropdown = document.getElementById("user-dropdown");
        const accountWrapper = document.getElementById("account-wrapper");
        let isDropdownOpen = false;
        let hideTimeout;

        // Fungsi untuk menampilkan dropdown
        function showDropdown() {
            clearTimeout(hideTimeout); // Batalkan timeout jika ada
            userDropdown.classList.remove("hidden");
        }

        // Fungsi untuk menyembunyikan dropdown
        function hideDropdown() {
            if (!isDropdownOpen) { // Hanya sembunyikan jika tidak diklik
                hideTimeout = setTimeout(() => {
                    userDropdown.classList.add("hidden");
                }, 200);
            }
        }

        // Event saat hover masuk (selalu tampilkan dropdown)
        accountWrapper.addEventListener("mouseenter", function() {
            showDropdown();
        });

        // Event saat hover keluar (hanya hilangkan jika belum diklik)
        accountWrapper.addEventListener("mouseleave", function() {
            hideDropdown();
        });

        // Event saat tombol diklik
        userMenuButton.addEventListener("click", function(event) {
            event.stopPropagation(); // Mencegah event klik menutup dropdown langsung
            if (isDropdownOpen) {
                isDropdownOpen = false;
                userDropdown.classList.add("hidden");
            } else {
                isDropdownOpen = true;
                showDropdown();
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
