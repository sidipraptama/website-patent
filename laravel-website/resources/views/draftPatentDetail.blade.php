<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Patent Detail</title>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @vite(['resources/js/app.js'])
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
    <div class="flex">
        <!-- Navbar -->
        <nav id="main-navbar"
            class="fixed top-0 left-0 right-0 w-full bg-white px-4 py-2 flex items-center h-[4rem] z-50 shadow-md">
            <!-- Back Button -->
            <a href="{{ route('draft-patent') }}"
                class="p-2 rounded-lg border border-gray-200 bg-white hover:bg-slate-100 transition flex items-center shadow-sm w-10 h-10 justify-center relative">
                <i class="fas fa-angle-left text-md text-slate-900"></i>
            </a>

            <!-- Auto Save & Save (Desktop only) -->
            <div class="hidden md:flex md:ml-4 ml-2 items-center gap-4">
                <!-- Save -->
                <button id="save-button" data-draft-id="{{ $draft->draft_id }}"
                    class="p-2 px-4 rounded-md text-white bg-customBlue hover:bg-customBlue-hover transition flex items-center h-10">
                    <i class="fas fa-save text-md"></i>
                    <span id="save-status" class="ml-2 text-sm">Saved</span>
                </button>

                <!-- Auto Save -->
                <label class="inline-flex items-center cursor-pointer">
                    <span class="mr-2 text-sm font-medium text-gray-800">Auto Save</span>
                    <input type="checkbox" id="auto-save-toggle" class="sr-only peer">
                    <div
                        class="relative w-11 h-6 bg-slate-200 hover:bg-slate-300 rounded-full peer-checked:hover:bg-customBlue-hover peer-checked:bg-customBlue peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:h-5 after:w-5 after:rounded-full after:transition-all transition">
                    </div>
                </label>
            </div>

            <!-- Title (centered) -->
            <div class="absolute left-1/2 transform -translate-x-1/2 text-center text-slate-900">
                <span class="text-lg font-semibold">Draft Patent Detail</span>
            </div>

            <!-- Desktop Account Dropdown -->
            <div id="account-wrapper" class="relative hidden md:block ml-auto">
                <button type="button" id="user-menu-button"
                    class="flex items-center gap-2 text-sm font-medium text-gray-800 bg-white border border-gray-200 p-2 focus:outline-none focus:bg-slate-100 rounded-lg">

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

            <!-- Mobile: Burger Button -->
            <div class="md:hidden ml-auto">
                <button id="mobile-menu-toggle" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-ellipsis-vertical text-xl text-slate-900"></i>
                </button>
            </div>
        </nav>

        <!-- Mobile Dropdown Menu -->
        <div id="mobile-dropdown"
            class="md:hidden hidden fixed top-[3.5rem] right-0 w-full bg-white border-t border-gray-200 z-40 h-[3.5rem]">
            <div class="px-4 py-2 flex items-center justify-between flex-wrap gap-4">

                <!-- Kiri: Auto Save & Save -->
                <div class="flex items-center gap-4 flex-wrap">
                    <!-- Save Button -->
                    <button id="save-button-mobile" data-draft-id="{{ $draft->draft_id }}"
                        class="flex items-center bg-customBlue hover:bg-customBlue-hover text-white px-4 p-2 rounded-lg text-sm">
                        <i class="fas fa-save text-md"></i>
                        <span id="save-status-mobile" class="ml-2 text-sm">Saved</span>
                    </button>

                    <!-- Auto Save Toggle -->
                    <label class="inline-flex items-center cursor-pointer">
                        <span class="mr-2 text-sm font-medium text-slate-900">Auto Save</span>
                        <input type="checkbox" id="auto-save-toggle-mobile" class="sr-only peer">
                        <div
                            class="relative w-11 h-6 bg-slate-200 hover:bg-slate-300 rounded-full peer-checked:hover:bg-customBlue-hover peer-checked:bg-customBlue peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:h-5 after:w-5 after:rounded-full after:transition-all transition">
                        </div>
                    </label>
                </div>

                <!-- Sign Out Button with Hover Tooltip -->
                <div x-data="{ show: false }" class="relative">
                    <form action="{{ route('logout') }}" method="POST" @mouseenter="show = true"
                        @mouseleave="show = false">
                        @csrf
                        <button type="submit"
                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-md text-sm relative z-10">
                            <i class="fas fa-sign-out-alt text-md"></i>
                        </button>
                    </form>
                    <!-- Tooltip -->
                    <div x-show="show" x-transition
                        class="absolute left-1/2 -translate-x-1/2 -bottom-10 bg-gray-800 text-white text-xs rounded py-1 px-2 shadow-md z-20 whitespace-nowrap">
                        Sign Out
                    </div>
                </div>
            </div>
        </div>

        <!-- Konten -->
        <main id="main-content" class="flex-1 transition-all duration-300 ease-in-out p-12 pt-32">
            <!-- Toggle Button with Tooltip -->
            <div id="toggleLeft" class="fixed top-20 left-6 z-30 group">
                <!-- Button -->
                <button id="toggleSidebar"
                    class="w-12 h-12 relative rounded-full flex items-center justify-center shadow-sm border border-gray-200 bg-white hover:bg-slate-100 group">
                    <i class="fas fa-bars font-bold text-customBlue"></i>

                    <!-- Tooltip -->
                    <span
                        class="absolute left-full ml-2 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none z-40">
                        Open Navigator
                    </span>
                </button>
            </div>

            <!-- Sidebar -->
            <aside id="sidebarLeft"
                class="fixed top-[3.5rem] left-0 z-30 bg-white h-screen p-4 pt-7 border-r border-gray-300 shadow-md w-[16rem] flex flex-col transition-transform transform -translate-x-full">

                <!-- Header -->
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-md font-semibold text-gray-800">Navigation</h2>
                    <button id="closeSidebarLeft" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Navigation List -->
                <nav class="flex-1 space-y-2 overflow-y-auto">
                    <button onclick="scrollToSection('judul')" class="block text-left py-1 hover:text-gray-800">
                        <span class="inline-block border-b border-gray-300 hover:border-gray-600">Judul</span>
                    </button>
                    <button onclick="scrollToSection('bidang-invensi')"
                        class="block text-left py-1 hover:text-gray-800">
                        <span class="inline-block border-b border-gray-300 hover:border-gray-600">Bidang Teknologi
                            Invensi</span>
                    </button>
                    <button onclick="scrollToSection('latar-belakang')"
                        class="block text-left py-1 hover:text-gray-800">
                        <span class="inline-block border-b border-gray-300 hover:border-gray-600">Latar Belakang
                            Invensi</span>
                    </button>
                    <button onclick="scrollToSection('ringkasan')" class="block text-left py-1 hover:text-gray-800">
                        <span class="inline-block border-b border-gray-300 hover:border-gray-600">Ringkasan
                            Invensi</span>
                    </button>
                    <button onclick="scrollToSection('uraian-lengkap')"
                        class="block text-left py-1 hover:text-gray-800">
                        <span class="inline-block border-b border-gray-300 hover:border-gray-600">Uraian Lengkap
                            Invensi</span>
                    </button>
                    <button onclick="scrollToSection('klaim')" class="block text-left py-1 hover:text-gray-800">
                        <span class="inline-block border-b border-gray-300 hover:border-gray-600">Klaim</span>
                    </button>
                    <button onclick="scrollToSection('abstrak')" class="block text-left py-1 hover:text-gray-800">
                        <span class="inline-block border-b border-gray-300 hover:border-gray-600">Abstrak</span>
                    </button>
                    <button onclick="scrollToSection('gambar')" class="block text-left py-1 hover:text-gray-800">
                        <span class="inline-block border-b border-gray-300 hover:border-gray-600">Gambar</span>
                    </button>
                </nav>
            </aside>

            <div class="mx-auto w-[60vw] min-w-[32rem]">
                <h6 class="text-md text-center font-medium text-gray-800">Initial Idea</h6>
                <div
                    class="mt-2 bg-white p-6 pt-7 rounded-xl
                    shadow-[0px_2px_3px_-1px_rgba(0,0,0,0.1),0px_1px_0px_0px_rgba(25,28,33,0.02),0px_0px_0px_1px_rgba(25,28,33,0.08)]
                    transition">
                    <div class="w-full h-full p-4 border rounded-lg ">
                        <p class="text-md text-gray-600">{{ $draft->similarityCheck->input_text }}</p>
                    </div>
                </div>
            </div>

            <div id="judul" class="mx-auto w-[60vw] min-w-[32rem] mt-7">
                <div class="flex flex-col items-start">
                    <h6 class="pl-6 text-md font-medium text-gray-800">Judul</h6>
                    <div
                        class="mt-2 bg-white p-6 pt-7 rounded-xl
                        shadow-[0px_2px_3px_-1px_rgba(0,0,0,0.1),0px_1px_0px_0px_rgba(25,28,33,0.02),0px_0px_0px_1px_rgba(25,28,33,0.08)]
                        transition w-full">
                        <input id="input-judul" type="text" value="{{ $draft->title }}"
                            class="w-full text-gray-600 focus:outline-none border-0 border-b border-gray-300 focus:border-customBlue transition-colors duration-300 ease-in-out bg-transparent px-0 py-1">
                    </div>
                </div>
            </div>

            <!-- Bidang Teknologi Invensi -->
            <div id="bidang-invensi" class="mx-auto w-[60vw] min-w-[32rem] mt-7">
                <div class="flex flex-col items-start">
                    <h6 class="pl-6 text-md font-medium text-gray-800">Bidang Teknologi Invensi</h6>
                    <div class="mt-2 bg-white p-6 pt-7 rounded-xl shadow transition w-full">
                        <div id="editor-bidang" contenteditable="true"
                            class="w-full min-h-[4rem] max-h-60 overflow-y-auto text-gray-600 focus:outline-none border-0 border-b border-gray-300 focus:border-customBlue transition-colors duration-300 ease-in-out bg-transparent px-0 py-1">
                            {!! $draft->technical_field !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latar Belakang Invensi -->
            <div id="latar-belakang" class="mx-auto w-[60vw] min-w-[32rem] mt-7">
                <div class="flex flex-col items-start">
                    <h6 class="pl-6 text-md font-medium text-gray-800">Latar Belakang Invensi</h6>
                    <div class="mt-2 bg-white p-6 pt-7 rounded-xl shadow transition w-full">
                        <div id="editor-latar" contenteditable="true"
                            class="w-full min-h-[8rem] max-h-60 overflow-y-auto text-gray-600 focus:outline-none border-0 border-b border-gray-300 focus:border-customBlue transition-colors duration-300 ease-in-out bg-transparent px-0 py-1">
                            {!! $draft->background !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Invensi -->
            <div id="ringkasan" class="mx-auto w-[60vw] min-w-[32rem] mt-7">
                <div class="flex flex-col items-start">
                    <h6 class="pl-6 text-md font-medium text-gray-800">Ringkasan Invensi</h6>
                    <div class="mt-2 bg-white p-6 pt-7 rounded-xl shadow transition w-full">
                        <div id="editor-ringkasan" contenteditable="true"
                            class="w-full min-h-[8rem] max-h-60 overflow-y-auto text-gray-600 focus:outline-none border-0 border-b border-gray-300 focus:border-customBlue transition-colors duration-300 ease-in-out bg-transparent px-0 py-1">
                            {!! $draft->summary !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Uraian Lengkap Invensi -->
            <div id="uraian-lengkap" class="mx-auto w-[60vw] min-w-[32rem] mt-7">
                <div class="flex flex-col items-start">
                    <h6 class="pl-6 text-md font-medium text-gray-800">Uraian Lengkap Invensi</h6>
                    <div class="mt-2 bg-white p-6 pt-7 rounded-xl shadow transition w-full">
                        <div id="editor-uraian" contenteditable="true"
                            class="w-full min-h-[8rem] max-h-60 overflow-y-auto text-gray-600 focus:outline-none border-0 border-b border-gray-300 focus:border-customBlue transition-colors duration-300 ease-in-out bg-transparent px-0 py-1">
                            {!! $draft->description !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Uraian Klaim -->
            <div id="klaim" class="mx-auto w-[60vw] min-w-[32rem] mt-7">
                <div class="flex flex-col items-start">
                    <h6 class="pl-6 text-md font-medium text-gray-800">Uraian Klaim</h6>
                    <div class="mt-2 bg-white p-6 pt-7 rounded-xl shadow transition w-full">
                        <div id="editor-klaim" contenteditable="true"
                            class="w-full min-h-[8rem] max-h-60 overflow-y-auto text-gray-600 focus:outline-none border-0 border-b border-gray-300 focus:border-customBlue transition-colors duration-300 ease-in-out bg-transparent px-0 py-1">
                            {!! $draft->claims !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Abstrak -->
            <div id="abstrak" class="mx-auto w-[60vw] min-w-[32rem] mt-7 mb-10">
                <div class="flex flex-col items-start">
                    <h6 class="pl-6 text-md font-medium text-gray-800">Abstrak</h6>
                    <div class="mt-2 bg-white p-6 pt-7 rounded-xl shadow transition w-full">
                        <div id="editor-abstrak" contenteditable="true"
                            class="w-full min-h-[8rem] max-h-60 overflow-y-auto text-gray-600 focus:outline-none border-0 border-b border-gray-300 focus:border-customBlue transition-colors duration-300 ease-in-out bg-transparent px-0 py-1">
                            {!! $draft->abstract !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gambar -->
            <div id="gambar" class="mx-auto w-[60vw] min-w-[32rem] mt-7 mb-7">
                <div class="flex flex-col items-start">
                    <h6 class="pl-6 text-md font-medium text-gray-800">Gambar</h6>

                    <!-- Container hanya untuk gambar -->
                    <div class="mt-2 bg-white p-6 pt-7 rounded-xl shadow transition w-full space-y-4"
                        id="image-container">
                        @foreach ($draft->images as $image)
                            <div class="relative group border justify-between border-gray-200 rounded p-4 flex items-center gap-4"
                                id="image-{{ $image->image_id }}">
                                <img src="{{ asset('storage/' . $image->file) }}"
                                    class="w-28 h-auto rounded shadow-sm" />

                                <!-- Tombol delete sebagai ikon silang -->
                                <button class="ml-auto delete-image text-gray-800 hover:text-gray-500 transition"
                                    data-id="{{ $image->image_id }}">
                                    <i class="fas fa-times text-lg"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <form id="image-upload-form" action="{{ route('draft-patent.store-image') }}" method="POST"
                        enctype="multipart/form-data" class="mt-4 w-full bg-white">
                        @csrf
                        <input type="hidden" name="draft_id" value="{{ $draft->draft_id }}"
                            id="image-input-hidden">

                        <!-- Label yang membungkus seluruh baris agar bisa diklik -->
                        <label for="image-input"
                            class="border-2 border-dashed border-gray-300 rounded-lg p-5 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-50 transition-all text-center">
                            <i class="fas fa-image text-customBlue text-3xl mb-2"></i>
                            <span class="text-sm text-gray-500">Klik untuk memilih gambar</span>
                            <span class="text-xs text-gray-400 mt-1">Maks: 2MB, JPG/PNG</span>
                        </label>

                        <!-- Input file tersembunyi -->
                        <input type="file" name="image" accept="image/*" id="image-input" class="hidden" />
                    </form>
                </div>
            </div>

            {{-- Side bar similar patent --}}
            <div id="toggleRight" class="fixed top-20 right-6 z-30 group">
                <button id="toggleHistorySidebar"
                    class="w-12 h-12 rounded-full flex items-center justify-center shadow-sm border border-gray-200 bg-white hover:bg-slate-100 group">
                    <i class="fas fa-project-diagram text-customBlue"></i>

                    <!-- Tooltip -->
                    <span
                        class="absolute right-full mr-2 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none z-40">
                        Open Similar Patents
                    </span>
                </button>
            </div>

            <aside id="sidebarHistory"
                class="fixed top-[3.5rem] right-0 bg-white h-screen p-4 pt-7 border-l border-gray-300 shadow-md w-[20rem] overflow-hidden z-30 transition-transform transform translate-x-full">

                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-md font-semibold text-gray-800">Similar Patents</h2>
                    <button id="closeSidebarRight" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Sidebar Content -->
                <div id="similarity-results" class="mt-2 space-y-2 max-h-[86vh] overflow-y-auto">
                    <!-- Result content here -->
                </div>
            </aside>
        </main>
    </div>
</body>

<script>
    function scrollToSection(id) {
        const section = document.getElementById(id);
        if (section) {
            section.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    }

    function syncSaveStatus(text) {
        $('#save-status').text(text);
        $('#save-status-mobile').text(text);
    }

    $(document).ready(function() {
        var checkId = "{{ $draft->similarityCheck->check_id }}";
        let isSaved = true;

        enableImagePreview();

        // Fetch similarity check results via AJAX
        $.ajax({
            url: `/similarity/check-results/${checkId}`,
            method: 'GET',
            success: function(response) {
                var similarityContent = '';
                console.log(response.check_results);
                if (response.check_results.length > 0) {
                    $.each(response.check_results, function(index, item) {
                        similarityContent += `
                    <div class="bg-white border-b border-gray-300 flex justify-between items-start py-3 relative cursor-pointer" onclick="window.open('https://patents.google.com/patent/US${item.patent_id}', '_blank')">
                        <div class="flex flex-col justify-start">
                            <h3 class="font-semibold text-sm text-gray-800 line-clamp-1">${item.patent_title}</h3>
                            <p class="text-xs text-gray-500 mt-2 line-clamp-3 overflow-hidden">
                                ${item.patent_abstract}</p>
                        </div>
                        <div class="my-6 mx-4">
                            <span class="text-xl font-bold text-gray-800">${(item.similarity_score * 100).toFixed(2)}%</span>
                        </div>
                    </div>
                `;
                    });
                } else {
                    similarityContent = '<p>No similarity results found.</p>';
                }
                $('#similarity-results').html(similarityContent);
            },
            error: function() {
                $('#similarity-results').html('<p>Error loading similarity results.</p>');
            }
        });

        var autoSaveStatus = localStorage.getItem('autoSaveEnabled');

        // Jika ada, set nilai toggle berdasarkan status yang disimpan
        if (autoSaveStatus === 'true') {
            $('#auto-save-toggle').prop('checked', true);
            $('#auto-save-toggle-mobile').prop('checked', true);
        } else {
            $('#auto-save-toggle').prop('checked', false);
            $('#auto-save-toggle-mobile').prop('checked', false);
        }

        // Auto save setup
        var autoSaveEnabled = $('#auto-save-toggle').prop('checked');
        var autoSaveEnabled = $('#auto-save-toggle-mobile').prop('checked');

        // Toggle Auto Save
        $('#auto-save-toggle, #auto-save-toggle-mobile').on('change', function() {
            autoSaveEnabled = this.checked;

            console.log('Auto Save:', autoSaveEnabled);
            // Simpan status ke localStorage
            localStorage.setItem('autoSaveEnabled', autoSaveEnabled);
        });

        // Save function
        function saveDraft(content, field) {
            var draftId = "{{ $draft->draft_id }}"; // Ambil draftId yang sesuai dengan data di Blade

            // Tampilkan status "Saving..."
            syncSaveStatus('Saving...')

            $.ajax({
                url: `/draft-patent/${draftId}/save`, // Gunakan route yang sesuai
                method: 'POST',
                data: {
                    field: field, // Nama field yang ingin diupdate
                    content: content, // Konten untuk field tersebut
                    _token: "{{ csrf_token() }}" // CSRF token untuk validasi
                },
                success: function(response) {
                    isSaved = true;
                    // Jika berhasil disimpan, update status menjadi "Saved"
                    syncSaveStatus('Saved')
                },
                error: function(xhr) {
                    // Jika gagal, tetap tampilkan status "Not Saved"
                    syncSaveStatus('Not Saved')
                }
            });
        }


        // Auto-save functionality: triggers when user inputs text in specified fields
        function autoSave() {
            $('#input-judul, #editor-bidang, #editor-latar, #editor-ringkasan, #editor-uraian, #editor-klaim, #editor-abstrak')
                .on('input', function() {
                    if (autoSaveEnabled) {
                        var field = $(this).attr('id');
                        var content = $(this).html() || $(this).val();

                        // Pemetaan ID elemen ke nama field yang valid di controller
                        var fieldMapping = {
                            'input-judul': 'title',
                            'editor-bidang': 'technical_field',
                            'editor-latar': 'background',
                            'editor-ringkasan': 'summary',
                            'editor-uraian': 'description',
                            'editor-klaim': 'claims',
                            'editor-abstrak': 'abstract',
                        };

                        var mappedField = fieldMapping[field]; // Menyambungkan ID ke field yang valid

                        if (mappedField) {
                            saveDraft(content, mappedField); // Kirim field yang valid
                        }
                    }
                });
        }

        // Initialize auto-save
        autoSave();

        // Manual save button (for regular saving)
        $('#save-button, #save-button-mobile').on('click', function() {
            let draftId = $(this).data('draft-id');
            let updateUrl = `/draft-patent/${draftId}/update`;

            let title = $('#input-judul').val();
            let technical_field = $('#editor-bidang').html();
            let background = $('#editor-latar').html();
            let summary = $('#editor-ringkasan').html();
            let description = $('#editor-uraian').html();
            let claim = $('#editor-klaim').html();
            let abstract = $('#editor-abstrak').html();

            syncSaveStatus('Saving...')

            $.ajax({
                url: updateUrl,
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    title: title,
                    technical_field: technical_field,
                    background: background,
                    summary: summary,
                    description: description,
                    claims: claim,
                    abstract: abstract,
                },
                success: function(response) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: response.message || 'Berhasil disimpan!',
                        showConfirmButton: false,
                        timer: 3000,
                        customClass: {
                            popup: 'mt-[1rem]',
                        }
                    });
                    isSaved = true;
                    syncSaveStatus('Saved')
                },
                error: function(xhr) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Gagal menyimpan. Periksa data Anda dan coba lagi.',
                        showConfirmButton: false,
                        timer: 3000,
                        customClass: {
                            popup: 'mt-[1rem]',
                        }
                    });
                }
            });
        });

        // Function to handle rich text editing (for bold, italic, and tab functionality)
        function handleRichText(e) {
            if (e.key.toLowerCase() === "b" && e.ctrlKey) {
                e.preventDefault();
                document.execCommand("bold");
            }

            if (e.key.toLowerCase() === "i" && e.ctrlKey) {
                e.preventDefault();
                document.execCommand("italic");
            }

            if (e.key === "Tab") {
                e.preventDefault();
                document.execCommand("insertText", false, "\t");
            }
        }

        // Confirmation before close and reload
        // Tandai bahwa ada perubahan saat input berubah
        const excludedIDs = new Set(["auto-save-toggle", "auto-save-toggle-mobile", "image-input",
            "image-input-hidden"
        ]);

        document.querySelectorAll("input, [contenteditable=true]").forEach(el => {
            el.addEventListener("input", () => {
                if (!excludedIDs.has(el.id)) {
                    isSaved = false;
                    syncSaveStatus('Not Saved');
                }
            });
        });

        // Saat tombol Save ditekan
        document.getElementById("save-button").addEventListener("click", function() {
            isSaved = true;
            syncSaveStatus('Saved')
        });

        document.getElementById("save-button-mobile").addEventListener("click", function() {
            isSaved = true;
            syncSaveStatus('Saved')
        });

        // Konfirmasi sebelum reload / close tab
        window.addEventListener("beforeunload", function(e) {
            if (!isSaved) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Attach the handleRichText function to the necessary fields (e.g., editors)
        $('#editor-bidang, #editor-latar, #editor-ringkasan, #editor-uraian, #editor-klaim, #editor-abstrak')
            .on(
                'keydown', handleRichText);

        // Handle image upload
        $('#image-input').on('change', function() {
            var formData = new FormData($('#image-upload-form')[0]);

            uploadImage(formData);
        });

        function enableImagePreview() {
            $('#image-container img').off('click').on('click', function() {
                const src = $(this).attr('src');
                Swal.fire({
                    imageUrl: src,
                    imageAlt: 'Gambar',
                    showConfirmButton: false
                });
            });
        }

        // Fungsi untuk upload gambar
        function uploadImage(formData) {
            $.ajax({
                url: $('#image-upload-form').attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Menangani sukses upload gambar
                    if (response.success) {
                        let newImageHTML = `
                            <div class="relative group border border-gray-200 rounded p-4 flex items-center gap-4" id="image-${response.image_id}">
                                <img src="${response.image_url}" class="w-28 h-auto rounded shadow-sm" />
                                <button class="ml-auto delete-image text-gray-800 hover:text-gray-500 transition" data-id="${response.image_id}">
                                    <i class="fas fa-times text-lg"></i>
                                </button>
                            </div>
                        `;

                        // Menambahkan gambar yang baru diupload ke dalam container
                        $('#image-container').append(newImageHTML);
                        enableImagePreview();

                        // Tampilkan notifikasi sukses
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: response.message || 'Gambar berhasil diupload!',
                            showConfirmButton: false,
                            timer: 3000,
                            customClass: {
                                popup: 'mt-[1rem]',
                            }
                        });

                        $('#image-upload-form')[0].reset();

                        // Menambahkan event listener untuk tombol delete yang baru
                        $('.delete-image').on('click', function() {
                            var imageId = $(this).data('id');
                            var imageElement = $('#image-' + imageId);

                            // Tanyakan konfirmasi kepada user
                            Swal.fire({
                                title: 'Apakah Anda yakin?',
                                text: 'Gambar ini akan dihapus secara permanen!',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, hapus!',
                                cancelButtonText: 'Tidak, batal!',
                                customClass: {
                                    popup: 'mt-[1rem]',
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Kirim permintaan untuk menghapus gambar jika konfirmasi berhasil
                                    $.ajax({
                                        url: '/draft-patent/delete-image/' +
                                            imageId,
                                        type: 'DELETE',
                                        data: {
                                            _token: "{{ csrf_token() }}"
                                        },
                                        success: function(response) {
                                            if (response.success) {
                                                // Jika berhasil, hapus elemen gambar dari halaman
                                                imageElement.remove();
                                                // Tampilkan notifikasi sukses
                                                Swal.fire({
                                                    toast: true,
                                                    position: 'top-end',
                                                    icon: 'success',
                                                    title: 'Gambar berhasil dihapus.',
                                                    showConfirmButton: false,
                                                    timer: 3000,
                                                    customClass: {
                                                        popup: 'mt-[1rem]',
                                                    }
                                                });
                                            } else {
                                                // Jika gagal, tampilkan notifikasi gagal
                                                Swal.fire({
                                                    toast: true,
                                                    position: 'top-end',
                                                    icon: 'error',
                                                    title: 'Terjadi kesalahan saat menghapus gambar.',
                                                    showConfirmButton: false,
                                                    timer: 3000,
                                                    customClass: {
                                                        popup: 'mt-[1rem]',
                                                    }
                                                });
                                            }
                                        },
                                        error: function(xhr, status,
                                            error) {
                                            let errorMessage =
                                                'Gambar gagal diupload.';

                                            if (xhr.responseJSON && xhr
                                                .responseJSON.errors) {
                                                const errors = xhr
                                                    .responseJSON
                                                    .errors;
                                                errorMessage = Object
                                                    .values(errors).map(
                                                        msgArr => msgArr
                                                        .join(', '))
                                                    .join(' ');
                                            } else if (xhr
                                                .responseJSON && xhr
                                                .responseJSON.message) {
                                                errorMessage = xhr
                                                    .responseJSON
                                                    .message;
                                            }

                                            Swal.fire({
                                                toast: true,
                                                position: 'top-end',
                                                icon: 'error',
                                                title: errorMessage,
                                                showConfirmButton: false,
                                                timer: 3000,
                                                customClass: {
                                                    popup: 'mt-[1rem]',
                                                }
                                            });
                                        }
                                    });
                                }
                            });
                        });

                    } else {
                        // Jika ada masalah, tampilkan pesan error
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: response.message || 'Gambar gagal diupload.',
                            showConfirmButton: false,
                            timer: 3000,
                            customClass: {
                                popup: 'mt-[1rem]',
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'Gambar gagal diupload.';

                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        errorMessage = Object.values(errors).map(msgArr => msgArr.join(', ')).join(
                            ' ');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: errorMessage,
                        showConfirmButton: false,
                        timer: 3000,
                        customClass: {
                            popup: 'mt-[1rem]',
                        }
                    });
                }

            });
        }

        // Fungsi untuk menghapus gambar dengan konfirmasi SweetAlert
        $('.delete-image').on('click', function() {
            var imageId = $(this).data('id');
            var imageElement = $('#image-' + imageId);

            // Tanyakan konfirmasi kepada user
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Gambar ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Tidak, batal!',
                customClass: {
                    popup: 'mt-[1rem]',
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Kirim permintaan untuk menghapus gambar jika konfirmasi berhasil
                    $.ajax({
                        url: '/draft-patent/delete-image/' + imageId,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.success) {
                                // Jika berhasil, hapus elemen gambar dari halaman
                                imageElement.remove();
                                // Tampilkan notifikasi sukses
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'success',
                                    title: 'Gambar berhasil dihapus.',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    customClass: {
                                        popup: 'mt-[1rem]',
                                    }
                                });
                            } else {
                                // Jika gagal, tampilkan notifikasi gagal
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'error',
                                    title: 'Terjadi kesalahan saat menghapus gambar.',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    customClass: {
                                        popup: 'mt-[1rem]',
                                    }
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            // Tampilkan notifikasi error
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: 'Terjadi kesalahan saat menghapus gambar.',
                                showConfirmButton: false,
                                timer: 3000,
                                customClass: {
                                    popup: 'mt-[1rem]',
                                }
                            });
                        }
                    });
                }
            });
        });
    });

    // Left Sidebar
    const toggleBtnLeft = document.getElementById('toggleSidebar');
    const sidebarLeft = document.getElementById('sidebarLeft');
    const closeBtnLeft = document.getElementById('closeSidebarLeft');

    toggleBtnLeft.addEventListener('click', () => {
        sidebarLeft.classList.toggle('-translate-x-full');
    });

    closeBtnLeft.addEventListener('click', () => {
        sidebarLeft.classList.add('-translate-x-full');
    });

    // Right Sidebar (History)
    const toggleBtnRight = document.getElementById('toggleHistorySidebar');
    const sidebarRight = document.getElementById('sidebarHistory');
    const closeBtnRight = document.getElementById('closeSidebarRight');

    toggleBtnRight.addEventListener('click', () => {
        sidebarRight.classList.toggle('translate-x-full');
    });

    closeBtnRight.addEventListener('click', () => {
        sidebarRight.classList.add('translate-x-full');
    });

    document.addEventListener("DOMContentLoaded", function() {
        const userMenuButton = document.getElementById("user-menu-button");
        const userDropdown = document.getElementById("user-dropdown");
        const accountWrapper = document.getElementById("account-wrapper");

        const mobileToggle = document.getElementById("mobile-menu-toggle");
        const mobileDropdown = document.getElementById("mobile-dropdown");
        const navbar = document.getElementById("main-navbar");
        const mainContent = document.getElementById("main-content");

        const toggleSidebar = document.getElementById("toggleLeft");
        const toggleHistorySidebar = document.getElementById("toggleRight");
        const sidebarLeft = document.getElementById("sidebarLeft");
        const sidebarHistory = document.getElementById("sidebarHistory");

        const desktopAutoToggle = document.getElementById("auto-save-toggle");
        const mobileAutoToggle = document.getElementById("auto-save-toggle-mobile");

        function syncToggles(source, target) {
            target.checked = source.checked;
        }

        // Saat desktop toggle berubah, ubah juga mobile toggle
        desktopAutoToggle.addEventListener("change", function() {
            syncToggles(desktopAutoToggle, mobileAutoToggle);
        });

        // Saat mobile toggle berubah, ubah juga desktop toggle
        mobileAutoToggle.addEventListener("change", function() {
            syncToggles(mobileAutoToggle, desktopAutoToggle);
        });


        // Toggle dropdown desktop (only on desktop)
        if (window.innerWidth >= 640) {
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
                userDropdown.classList.toggle("hidden", !isDropdownOpen);
            });

            document.addEventListener("click", function(event) {
                if (!accountWrapper.contains(event.target)) {
                    isDropdownOpen = false;
                    userDropdown.classList.add("hidden");
                }
            });
        }

        // Mobile menu toggle
        mobileToggle.addEventListener("click", function() {
            mobileDropdown.classList.toggle("hidden");

            const isDropdownVisible = !mobileDropdown.classList.contains("hidden");

            // Hilangkan shadow dari navbar saat dropdown muncul
            if (isDropdownVisible) {
                navbar.classList.remove("shadow-md");
                mobileDropdown.classList.add("shadow-md");

                mainContent.classList.add("pt-12");

                sidebarLeft.classList.remove("top-[3.5rem]");
                sidebarLeft.classList.add("top-[7rem]");
                sidebarHistory.classList.remove("top-[3.5rem]");
                sidebarHistory.classList.add("top-[7rem]");
                toggleSidebar.classList.remove("top-20");
                toggleHistorySidebar.classList.remove("top-20");
                toggleSidebar.classList.add("top-32");
                toggleHistorySidebar.classList.add("top-32");
            } else {
                navbar.classList.add("shadow-md");
                mobileDropdown.classList.remove("shadow-md");

                mainContent.classList.remove("pt-12");

                sidebarLeft.classList.remove("top-[7rem]");
                sidebarLeft.classList.add("top-[3.5rem]");
                sidebarHistory.classList.remove("top-[7rem]");
                sidebarHistory.classList.add("top-[3.5rem]");
                toggleSidebar.classList.remove("top-32");
                toggleHistorySidebar.classList.remove("top-32");
                toggleSidebar.classList.add("top-20");
                toggleHistorySidebar.classList.add("top-20");
            }
        });

        window.addEventListener("resize", function() {
            // Cek jika layar >= breakpoint sm (Tailwind sm = 640px)
            if (window.innerWidth >= 640) {
                // Reset tampilan ke default untuk desktop

                // Sembunyikan mobile dropdown jika sebelumnya aktif
                mobileDropdown.classList.add("hidden");
                mobileDropdown.classList.remove("shadow-md");

                // Reset navbar shadow
                navbar.classList.add("shadow-md");

                // Reset main content padding
                mainContent.classList.remove("pt-12");

                // Reset semua posisi sidebar dan toggle
                sidebarLeft.classList.remove("top-[7rem]");
                sidebarLeft.classList.add("top-[3.5rem]");
                sidebarHistory.classList.remove("top-[7rem]");
                sidebarHistory.classList.add("top-[3.5rem]");

                toggleSidebar.classList.remove("top-32");
                toggleHistorySidebar.classList.remove("top-32");
                toggleSidebar.classList.add("top-20");
                toggleHistorySidebar.classList.add("top-20");
            }
        });
    });
</script>

</html>
