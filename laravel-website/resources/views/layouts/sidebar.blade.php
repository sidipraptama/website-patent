<aside :class="sidebarOpen ? 'w-[14rem]' : 'w-[4.5rem]'"
    class="fixed left-0 bg-white h-screen p-3 pt-4 transition-all duration-300 ease-in-out z-50 border border-gray-200 shadow-md">

    <!-- Logo -->
    <div x-show="sidebarOpen" class="flex justify-center items-center mt-3">
        <a class="w-[100%] flex justify-center items-center" href="{{ route('landing') }}">
            <img src="{{ asset('images/logoutama.svg') }}" alt="Paten.AI Logo" class="h-12 w-[62%]">
        </a>
    </div>

    <!-- Toggle Button -->
    <div class="border border-gray-200 rounded-lg"
        :class="sidebarOpen ? 'absolute top-4 -right-5' :
            'absolute top-4 left-1/2 -translate-x-1/2'">
        <button @click="toggleSidebar" :title="sidebarOpen ? 'Tutup Sidebar' : 'Buka Sidebar'"
            class="p-2 w-10 h-10 flex items-center justify-center rounded-lg bg-white text-customBlue hover:bg-slate-100 transition duration-200 ease-in-out">
            <i :class="sidebarOpen ? 'fas fa-angle-double-left' : 'fas fa-angle-double-right'"></i>
        </button>
    </div>

    <ul class="text-white" :class="sidebarOpen ? 'mt-0' : 'mt-12'">
        <li class="py-2 mt-4">
            <a href="{{ route('dashboard') }}"
                class="flex items-center px-3 py-2 rounded-lg transition-all ease-in-out duration-200
                    {{ request()->routeIs('dashboard') ? 'bg-customBlue font-semibold hover:bg-customBlue-hover' : 'text-customBlue hover:bg-slate-100 hover:text-customBlue-hover' }}">
                <i class="fas fa-chart-bar fa-fw flex-shrink-0 mx-0.5"></i>
                <span class="text-base font-medium overflow-hidden whitespace-nowrap"
                    :class="sidebarOpen ? 'ml-3 block' : 'ml-3 opacity-0'">
                    Dashboard
                </span>
            </a>
        </li>
        <li class="py-2">
            <a href="{{ route('similarity-search') }}"
                class="flex items-center px-3 py-2 rounded-lg transition-all ease-in-out duration-200
                    {{ request()->routeIs('similarity-search') ? 'bg-customBlue font-semibold hover:bg-customBlue-hover' : 'text-customBlue hover:bg-slate-100 hover:text-customBlue-hover' }}">
                <i class="fas fa-clone fa-fw flex-shrink-0 mx-0.5"></i>
                <span class="text-base font-medium overflow-hidden whitespace-nowrap"
                    :class="sidebarOpen ? 'ml-3 block' : 'ml-3 opacity-0'">
                    Similarity Search
                </span>
            </a>
        </li>
        <li class="py-2">
            <a href="{{ route('draft-patent') }}"
                class="flex items-center px-3 py-2 rounded-lg transition-all ease-in-out duration-200
                    {{ request()->routeIs('draft-patent') ? 'bg-customBlue font-semibold hover:bg-customBlue-hover' : 'text-customBlue hover:bg-slate-100 hover:text-customBlue-hover' }}">
                <i class="fas fa-file-alt fa-fw flex-shrink-0 mx-0.5"></i>
                <span class="text-base font-medium overflow-hidden whitespace-nowrap"
                    :class="sidebarOpen ? 'ml-3 block' : 'ml-3 opacity-0'">
                    Draft Patents
                </span>
            </a>
        </li>
        <li class="py-2">
            <a href="{{ route('patent-search') }}"
                class="flex items-center px-3 py-2 rounded-lg transition-all ease-in-out duration-200
                    {{ request()->routeIs('patent-search') ? 'bg-customBlue font-semibold hover:bg-customBlue-hover' : 'text-customBlue hover:bg-slate-100 hover:text-customBlue-hover' }}">
                <i class="fas fa-search fa-fw flex-shrink-0 mx-0.5"></i>
                <span class="text-base font-medium overflow-hidden whitespace-nowrap"
                    :class="sidebarOpen ? 'ml-3 block' : 'ml-3 opacity-0'">
                    Patent Search
                </span>
            </a>
        </li>
        <li class="py-2">
            <a href="{{ route('bookmarks') }}"
                class="flex items-center px-3 py-2 rounded-lg transition-all ease-in-out duration-200
                    {{ request()->routeIs('bookmarks') ? 'bg-customBlue font-semibold hover:bg-customBlue-hover' : 'text-customBlue hover:bg-slate-100 hover:text-customBlue-hover' }}">
                <i class="fas fa-bookmark fa-fw flex-shrink-0 mx-0.5"></i>
                <span class="text-base font-medium overflow-hidden whitespace-nowrap"
                    :class="sidebarOpen ? 'ml-3 block' : 'ml-3 opacity-0'">
                    Bookmarks
                </span>
            </a>
        </li>

        @if (auth()->user()->isAdmin())
            <li class="py-2 mt-auto">
                <a href="{{ route('auto-update-log') }}"
                    class="flex items-center px-3 py-2 rounded-lg transition-all ease-in-out duration-200
                        {{ request()->routeIs('auto-update-log') ? 'bg-customBlue font-semibold hover:bg-customBlue-hover' : 'text-customBlue hover:bg-slate-100 hover:text-customBlue-hover' }}">
                    <i class="fas fa-cogs fa-fw flex-shrink-0 mx-0.5"></i>
                    <span class="hover:bg-slate-100text-base font-medium overflow-hidden whitespace-nowrap"
                        :class="sidebarOpen ? 'ml-3 block' : 'ml-3 opacity-0'">
                        Auto Update Logs
                    </span>
                </a>
            </li>
        @endif
    </ul>

    <!-- Copyright Section - Moves to the bottom -->
    <div class="absolute bottom-4 left-4 text-xs text-customBlue p-3 overfslate-100 hover:text-customBlue-hovern transition-all duration-300 ease-in-out"
        :class="{ 'opacity-0 scale-95': !sidebarOpen, 'opacity-100 scale-100': sidebarOpen }">
        <span>&copy; 2025 patent.ai. All rights reserved.</span>
    </div>
</aside>
