@extends('layouts.app')

@section('title', 'Similarity Search')

@section('content')
    <div class="p-6 md:px-32 md:py-8 transition-all duration-300 ease-in-out min-w-[32rem]">
        <x-navbar />

        <div>
            <div x-data="{ sidebarHistoryOpen: false }" class="rounded-lg min-w-[32rem] transition-all duration-300 ease-in-out"
                :class="sidebarHistoryOpen ? 'lg:mr-[16rem]' : ''">
                <!-- Sidebar History -->
                <aside x-show="sidebarHistoryOpen" x-transition:enter="transition ease-in-out duration-300 transform"
                    x-transition:enter-start="opacity-0 translate-x-full" x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in-out duration-300 transform"
                    x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-full"
                    class="z-50 fixed top-0 right-0 bg-white h-screen p-4 pt-8 shadow-md w-[16rem] flex flex-col">
                    <!-- Header -->
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-md font-semibold text-gray-800">Search History</h2>
                        <button id="closeSidebar" class="text-gray-800 hover:text-gray-500"
                            @click="sidebarHistoryOpen = !sidebarHistoryOpen">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Scrollable Area -->
                    <div class="relative grow min-h-full overflow-y-auto">
                        <!-- Empty State -->
                        <div id="historyEmpty"
                            class="hidden absolute inset-0 flex items-center justify-center text-gray-800 text-sm p-4">
                            <div class="flex flex-col items-center text-center">
                                <i class="fas fa-history text-4xl mb-3"></i>
                                <p>No search history available.</p>
                            </div>
                        </div>

                        <!-- History List -->
                        <div id="historyList" class="mt-2 space-y-2 max-h-[85vh] overflow-y-auto"></div>
                    </div>
                </aside>

                <!-- Input Sebelum Search -->
                <div id="searchWrapper"
                    class="flex flex-col items-center justify-center w-full h-[50rem] min-w-[32rem] max-w-[48rem] mx-auto">
                    <div class="bg-white shadow-md rounded-xl p-6 pt-8 w-full">
                        <div x-data="{ showGuide: true }" x-show="showGuide" class="relative mb-4 w-full">
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-800 p-4 rounded-lg text-sm">
                                <strong class="block font-semibold mb-1">Abstract Input Guide:</strong>
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Use clear and descriptive language to explain your idea or invention.</li>
                                    <li>The text should be at least <strong>200 characters long</strong> to ensure optimal
                                        analysis by the system.</li>
                                    <li>Include key details such as the purpose, how it works, and what makes it unique.
                                    </li>
                                    <li>Avoid including personal or sensitive information.</li>
                                    <li>Example: "An automated system for feeding pets using IoT and remote monitoring..."
                                    </li>
                                </ul>
                            </div>
                            <button @click="showGuide = false"
                                class="absolute top-2 right-2 text-yellow-600 hover:text-yellow-800 focus:outline-none text-lg font-bold">
                                &times;
                            </button>
                        </div>

                        <form method="POST" id="similarityForm">
                            @csrf
                            <textarea name="abstract" id="abstractInput"
                                class="w-full h-48 max-h-[32rem] min-h-[8rem] p-4 border border-gray-300 text-md text-gray-600 rounded-lg
                               placeholder-gray-400 transition-all duration-300 ease-in-out
                               focus:ring-1 focus:ring-customBlue focus:border-customBlue focus:outline-none"
                                placeholder="Describe your idea...">{{ old('abstract') }}</textarea>
                            <div class="flex items-center mt-2 justify-end">
                                <button type="button" @click="sidebarHistoryOpen = !sidebarHistoryOpen"
                                    :class="sidebarHistoryOpen ? 'bg-customBlue hover:bg-customBlue-hover text-white' :
                                        'bg-slate-50 hover:bg-slate-100 text-customBlue'"
                                    class="btn-open-sidebar text-sm px-4 py-2 rounded-lg flex items-center transition-all duration-300 ease-in-out">
                                    <i class="fas fa-history text-xs mr-2"></i> History
                                </button>
                                <button type="submit"
                                    class="ml-2 bg-customBlue text-white text-sm px-4 py-2 rounded-lg hover:bg-customBlue-hover flex items-center transition-all duration-300 ease-in-out">
                                    <i class="fas fa-search text-xs mr-2"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Setelah Search -->
                <div id="resultWrapper" class="hidden w-full min-w-[32rem] max-w-[52rem] mx-auto mt-4">
                    <div class="bg-white shadow-md rounded-xl p-6 pt-8 mx-auto">
                        <!-- Tampilkan hasil abstract -->
                        <div role="abstractDisplay"
                            class="w-full h-48 p-4 border rounded-lg text-md text-gray-600 whitespace-pre-line overflow-y-auto">
                            {{ old('abstract') }}
                        </div>
                        <div class="flex items-center mt-2 justify-end">
                            <button type="button" @click="sidebarHistoryOpen = !sidebarHistoryOpen"
                                :class="sidebarHistoryOpen ? 'bg-customBlue hover:bg-customBlue-hover text-white' :
                                    'bg-slate-50 hover:bg-slate-100 text-customBlue'"
                                class="btn-open-sidebar text-sm px-4 py-2 rounded-lg flex items-center">
                                <i class="fas fa-history text-xs mr-2"></i> History
                            </button>
                            <button type="button" id="proceedToDraftBtn"
                                class="ml-2 bg-customBlue text-white text-sm px-4 py-2 rounded-lg hover:bg-customBlue-hover flex items-center">
                                <i class="fas fa-search text-xs mr-2"></i> Proceed to Draft Patent
                            </button>
                        </div>
                    </div>
                </div>

                <div id="resultContainer" class="space-y-4 w-full min-w-[32rem] max-w-[52rem] mx-auto mt-4">
                </div>
            </div>
        </div>

        <div id="loader" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-80 hidden">
            <div class="flex flex-col items-center">
                <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-gray-600"></div>
                <p class="mt-4 text-gray-700 font-medium">Please wait...</p>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let selectedHistoryId = null

        $('.btn-open-sidebar').click(function() {
            $('#sidebarHistory').fadeIn();
            fetchSearchHistory();
        });

        function fetchSearchHistory() {
            $.ajax({
                url: "{{ route('similarity.history') }}",
                type: "GET",
                success: function(response) {
                    let data = response.data; // penting!
                    let $list = $('#historyList');
                    $list.empty();

                    if (data.length === 0) {
                        $('#historyEmpty').show();
                        return;
                    }

                    $('#historyEmpty').hide();

                    data.forEach(item => {
                        let isActive = item.check_id === selectedHistoryId;
                        console.log(isActive);
                        let html = `
                        <div class="history-card p-3 rounded-lg text-sm transition-colors duration-150 cursor-pointer ${isActive ? 'bg-customBlue hover:bg-customBlue-hover text-white' : 'hover:bg-gray-100 text-gray-800'}"
                            data-id="${item.check_id}">
                            <p class="font-semibold line-clamp-2">${item.input_text}</p>
                            <span class="text-xs mt-2 font-light">
                                ${new Date(item.created_at).toLocaleString('id-ID', {
                                    day: '2-digit',
                                    month: '2-digit',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: false
                                })}
                            </span>
                        </div>`;
                        $list.append(html);
                    });

                    $list.find('[data-id]').click(function() {
                        const id = $(this).data('id');
                        selectedHistoryId = id;

                        // Hapus semua highlight terlebih dahulu
                        $list.find('.history-card')
                            .removeClass('bg-customBlue hover:bg-customBlue-hover text-white')
                            .addClass('hover:bg-gray-100 text-gray-800');

                        // Tambahkan highlight ke elemen yang diklik
                        $(this)
                            .removeClass('hover:bg-gray-100 text-gray-800')
                            .addClass('bg-customBlue hover:bg-customBlue-hover text-white');

                        $('#loader').removeClass('hidden');
                        $('body').css('overflow', 'hidden');

                        $.ajax({
                            url: `/similarity/results/${id}`,
                            method: 'GET',
                            success: function(response) {
                                $('#searchWrapper').addClass('hidden');
                                $('#resultWrapper').removeClass('hidden');
                                $('#resultContainer').html('');

                                // tampilkan abstract sebelumnya
                                $('#resultWrapper div[role="abstractDisplay"]').text(
                                    response.similarity_check.input_text);

                                // Update tampilan tombol Proceed to Draft
                                const proceedBtn = $('#proceedToDraftBtn');
                                if (response.has_draft) {
                                    proceedBtn.prop('disabled', true)
                                        .addClass('opacity-50 cursor-not-allowed')
                                        .html(
                                            '<i class="fas fa-ban text-xs mr-2"></i> Draft Sudah Dibuat'
                                        );
                                } else {
                                    proceedBtn.prop('disabled', false)
                                        .removeClass('opacity-50 cursor-not-allowed')
                                        .html(
                                            '<i class="fas fa-search text-xs mr-2"></i> Proceed to Draft Patent'
                                        );
                                }

                                if (response.check_results && response.check_results
                                    .length > 0) {
                                    let html = '';
                                    response.check_results.forEach(function(result) {
                                        html += `
                                    <div class="bg-white shadow-md rounded-lg p-6 flex justify-between items-center hover:shadow-lg relative group" onclick="window.open('https://patents.google.com/patent/US${result.patent_id}', '_blank')">
                                        <button class="absolute top-4 right-4 text-gray-500 z-10" onclick="event.stopPropagation(); toggleBookmark('${result.patent_id}');">
                                            <i class="fas fa-bookmark text-lg ${result.is_bookmarked ? 'text-yellow-400 hover:text-yellow-300' : 'text-gray-400 hover:text-gray-200'}" id="bookmark-icon-${result.patent_id}"></i>
                                        </button>
                                        <div class="flex flex-col justify-start">
                                            <h3 class="font-semibold text-lg mr-1 line-clamp-2">${result.patent_title}</h3>
                                            <div class="flex text-gray-700 mt-1 text-xs">
                                                <span>ID: ${result.patent_id}</span>
                                                <span class="ml-4">Granted: ${result.patent_date}</span>
                                                <span class="ml-4">Type: ${result.patent_type}</span>
                                            </div>
                                            <p class="text-gray-500 text-sm mt-2 text-justify line-clamp-4">${result.patent_abstract}</p>
                                        </div>
                                        <div class="pl-6 flex items-center">
                                            <span class="text-3xl font-bold text-gray-800">${(result.similarity_score * 100).toFixed(3)}%</span>
                                        </div>
                                    </div>
                                    `;
                                    });
                                    $('#resultContainer').html(html);
                                } else {
                                    $('#resultContainer').html(
                                        `<p class="text-gray-600">No results found.</p>`
                                    );
                                }

                                $('#loader').addClass('hidden');
                                $('body').css('overflow', 'auto');
                            },
                            error: function() {
                                $('#loader').addClass('hidden');
                                $('body').css('overflow', 'auto');
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'error',
                                    title: 'Gagal menampilkan riwayat.',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    customClass: {
                                        popup: 'mt-[1rem]',
                                    }
                                });
                            }
                        });
                    });
                },
                error: function() {
                    $('#historyList').html(
                        '<div class="text-center text-red-500 text-sm py-6">Gagal memuat riwayat.</div>');
                }
            });
        }

        $('#similarityForm').on('submit', function(e) {
            e.preventDefault();

            const abstract = $('#abstractInput').val();
            const token = $('input[name="_token"]').val();

            // Validasi panjang karakter minimum 200
            if (abstract.length < 200) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'Text input harus memiliki minimal 200 karakter.',
                    showConfirmButton: false,
                    timer: 3000,
                    customClass: {
                        popup: 'mt-[1rem]',
                    }
                });
                return;
            }

            $.ajax({
                url: "{{ route('similarity.search') }}",
                method: "POST",
                data: {
                    _token: token,
                    abstract: abstract
                },
                beforeSend: function() {
                    $('#loader').removeClass('hidden');
                    $('body').css('overflow', 'hidden');
                },
                success: function(response) {
                    // bersihkan hasil sebelumnya
                    $('#resultWrapper div[role="abstractDisplay"]').text(abstract);
                    $('#searchWrapper').addClass('hidden');
                    $('#resultWrapper').removeClass('hidden');
                    $('#resultContainer').html('');

                    if (response.data && response.data.length > 0) {
                        let html = '';
                        response.data.forEach(function(result) {
                            html += `
                        <div class="bg-white shadow-md rounded-lg p-6 flex justify-between items-center hover:shadow-lg relative group" onclick="window.open('https://patents.google.com/patent/US${result.patent_id}', '_blank')">
                            <!-- Bookmark Button -->
                            <button class="absolute top-4 right-4 text-gray-500 z-10" onclick="event.stopPropagation(); toggleBookmark('${result.patent_id}');">
                                <i class="fas fa-bookmark text-lg ${result.is_bookmarked ? 'text-blue-500 hover:text-blue-300' : 'text-gray-400 hover:text-gray-200'}" id="bookmark-icon-${result.patent_id}"></i>
                            </button>

                            <!-- Patent Content -->
                            <div class="flex flex-col justify-start">
                                <h3 class="font-semibold text-lg">${result.patent_title}</h3>
                                <div class="flex text-gray-700 mt-1 text-sm">
                                    <span>ID: ${result.patent_id}</span>
                                    <span class="ml-4">Granted: ${result.patent_date}</span>
                                    <span class="ml-4">Type: ${result.patent_type}</span>
                                </div>
                                <p class="text-gray-500 text-sm mt-2 text-justify line-clamp-4">${result.patent_abstract}</p>
                            </div>

                            <div class="pl-6 flex items-center">
                                <span class="text-4xl font-bold text-gray-800">${(result.score * 100).toFixed(3)}%</span>
                            </div>
                        </div>
                        `;
                        });
                        $('#resultContainer').html(html);
                    } else {
                        $('#resultContainer').html(`<p class="text-gray-600">No results found.</p>`);
                    }

                    $('#loader').addClass('hidden');
                    $('body').css('overflow', 'auto');

                    selectedHistoryId = response.check_id;
                    fetchSearchHistory()
                },
                error: function(xhr) {
                    $('#loader').addClass('hidden');
                    $('body').css('overflow', 'auto');

                    let errorMessage = 'Failed to get similarity results.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage = response.message;
                        } else if (response.error) {
                            errorMessage = response.error;
                        }
                    } catch (e) {
                        errorMessage = xhr.statusText || errorMessage;
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
        });

        $('#proceedToDraftBtn').on('click', function() {
            if (!selectedHistoryId) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'Pilih Riwayat Terlebih Dahulu',
                    showConfirmButton: false,
                    timer: 3000,
                    customClass: {
                        popup: 'mt-[1rem]',
                    }
                });
                return;
            }

            $.ajax({
                url: "{{ route('draft-patent.create') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    check_id: selectedHistoryId
                },
                beforeSend: function() {
                    $('#loader').removeClass('hidden');
                    $('body').css('overflow', 'hidden');
                },
                success: function(response) {
                    $('#loader').addClass('hidden');
                    $('body').css('overflow', 'auto');

                    // Menonaktifkan tombol setelah berhasil membuat draft
                    $('#proceedToDraftBtn').prop('disabled', true).addClass(
                            'opacity-50 cursor-not-allowed')
                        .html('<i class="fas fa-ban text-xs mr-2"></i> Draft Sudah Dibuat');

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: response.message || 'Draft Dibuat',
                        showConfirmButton: false,
                        timer: 2000,
                        customClass: {
                            popup: 'mt-[1rem]',
                        }
                    }).then(() => {
                        if (response.redirect_url) {
                            window.open(response.redirect_url, '_blank');
                        } else {
                            console.error('Redirect URL tidak tersedia di response.');
                        }
                    });
                },
                error: function(xhr) {
                    $('#loader').addClass('hidden');
                    $('body').css('overflow', 'auto');
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: xhr.responseJSON?.message || 'Gagal Membuat Draft',
                        showConfirmButton: false,
                        timer: 3000,
                        customClass: {
                            popup: 'mt-[1rem]',
                        }
                    });
                }
            });
        });

        function toggleBookmark(patentId) {
            let userId = "{{ auth()->user()->id ?? '' }}";

            // console.log(userId);
            // console.log(patentId);

            if (!userId) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'Please log in to save bookmarks.',
                    showConfirmButton: false,
                    timer: 3000,
                    customClass: {
                        popup: 'mt-[1rem]',
                    }
                });
                return;
            }

            $.ajax({
                url: "{{ route('patent.bookmark') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    user_id: userId,
                    patent_id: patentId,
                },
                success: function(response) {
                    if (response.status === "added") {
                        $(`#bookmark-icon-${patentId}`).removeClass("text-gray-400");
                        $(`#bookmark-icon-${patentId}`).removeClass("hover:text-gray-200");
                        $(`#bookmark-icon-${patentId}`).addClass("text-yellow-400");
                        $(`#bookmark-icon-${patentId}`).addClass("hover:text-yellow-300");
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Bookmark added!',
                            showConfirmButton: false,
                            timer: 3000,
                            customClass: {
                                popup: 'mt-[1rem]',
                            }
                        });
                    } else if (response.status === "confirm-removal") {
                        // Trigger confirmation before unbookmarking
                        Swal.fire({
                            title: 'Remove Bookmark?',
                            text: 'Are you sure you want to remove this bookmark?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, remove it!',
                            cancelButtonText: 'Cancel',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Call again with a flag to actually remove
                                $.ajax({
                                    url: "{{ route('patent.bookmark') }}",
                                    type: "POST",
                                    data: {
                                        _token: "{{ csrf_token() }}",
                                        user_id: userId,
                                        patent_id: patentId,
                                        confirm_removal: true
                                    },
                                    success: function(res) {
                                        if (res.status === "removed") {
                                            $(`#bookmark-icon-${patentId}`).addClass(
                                                "text-gray-400");
                                            $(`#bookmark-icon-${patentId}`).addClass(
                                                "hover:text-gray-200");
                                            $(`#bookmark-icon-${patentId}`).removeClass(
                                                "text-yellow-400");
                                            $(`#bookmark-icon-${patentId}`).removeClass(
                                                "hover:text-yellow-300");
                                            Swal.fire({
                                                toast: true,
                                                position: 'top-end',
                                                icon: 'success',
                                                title: 'Bookmark removed!',
                                                showConfirmButton: false,
                                                timer: 3000,
                                                customClass: {
                                                    popup: 'mt-[1rem]',
                                                }
                                            });
                                        }
                                    },
                                    error: function() {
                                        Swal.fire({
                                            toast: true,
                                            position: 'top-end',
                                            icon: 'error',
                                            title: 'Failed to remove bookmark.',
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
                    }
                },
                error: function() {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Failed to save bookmark.',
                        showConfirmButton: false,
                        timer: 3000,
                        customClass: {
                            popup: 'mt-[1rem]',
                        }
                    });
                }
            });
        }
    </script>
@endsection
