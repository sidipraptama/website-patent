<div>
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
</div>

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const userMenuButton = document.getElementById("user-menu-button");
            const userDropdown = document.getElementById("user-dropdown");
            const accountWrapper = document.getElementById("account-wrapper");
            let isDropdownOpen = false;
            let hideTimeout;

            function showDropdown() {
                clearTimeout(hideTimeout);
                userDropdown.classList.remove("hidden");
            }

            function hideDropdown() {
                if (!isDropdownOpen) {
                    hideTimeout = setTimeout(() => {
                        userDropdown.classList.add("hidden");
                    }, 200);
                }
            }

            accountWrapper.addEventListener("mouseenter", showDropdown);
            accountWrapper.addEventListener("mouseleave", hideDropdown);

            userMenuButton.addEventListener("click", function(event) {
                event.stopPropagation();
                isDropdownOpen = !isDropdownOpen;
                if (isDropdownOpen) {
                    showDropdown();
                } else {
                    userDropdown.classList.add("hidden");
                }
            });

            document.addEventListener("click", function(event) {
                if (!accountWrapper.contains(event.target)) {
                    isDropdownOpen = false;
                    userDropdown.classList.add("hidden");
                }
            });
        });
    </script>
@endpush
