@extends('layouts.app')

@section('title', 'Bookmarks')

@section('content')
    <div class="p-6 md:px-32 md:py-8 transition-all duration-300 ease-in-out">
        <x-navbar />

        <div class="p-6 bg-white rounded-lg shadow-md">
            <!-- Result Count -->
            <p class="mt-2 text-sm font-medium text-gray-500 ml-3" id="result-count">0 patents found</p>

            {{-- Devider --}}
            <hr class="mt-2 h-[1px] border-t-0 bg-neutral-200" />
            <!-- Grid Container -->
            <div class="grid grid-cols-1 mt-6" id="bookmark-list">
                <div id="loading-spinner" class="hidden flex items-center justify-center py-6">
                    <svg class="animate-spin h-8 w-8 text-customBlue" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function fetchBookmarks() {
            $("#loading-spinner").removeClass("hidden");

            $.ajax({
                url: "{{ route('bookmarks.list') }}",
                type: "GET",
                success: function(response) {
                    let results = response.results || [];

                    $("#result-count").text(`${response.total} bookmarks found`);

                    if (results.length === 0) {
                        $("#bookmark-list").html(`
                        <div class="flex flex-col items-center justify-center py-12 text-center text-gray-600">
                            <img src="/images/no_data_found.svg" alt="No data" class="w-64 h-auto mb-6 opacity-90">
                            <h2 class="text-xl font-semibold mb-2">No bookmarks found</h2>
                            <p class="text-gray-500">You haven't bookmarked any patents yet.</p>
                        </div>
                    `);
                    } else {
                        let html = results.map(result => {
                            return `
                            <div class="relative bg-white p-4 cursor-pointer transition duration-300" onclick="window.open('https://patents.google.com/patent/US${result.patent_id}', '_blank')">
                                <button class="absolute top-4 right-4 text-gray-500" onclick="event.stopPropagation(); toggleBookmark('${result.patent_id}');">
                                    <i class="fas fa-bookmark text-lg ${result.is_bookmarked ? 'text-yellow-400 hover:text-yellow-300' : 'text-gray-400 hover:text-gray-200'}" id="bookmark-icon-${result.patent_id}"></i>
                                </button>
                                <h3 class="font-semibold text-lg">${result.patent_title}</h3>
                                <div class="flex text-gray-700 mt-1 text-xs">
                                    <span>ID: ${result.patent_id}</span>
                                    <span class="ml-4">Granted: ${result.patent_date ?? '-'}</span>
                                    <span class="ml-4">Type: ${result.patent_type ?? '-'}</span>
                                </div>
                                <p class="text-gray-500 text-sm mt-2">${result.patent_abstract}</p>
                                <hr class="mt-6 border-t border-gray-200" />
                            </div>
                        `;
                        }).join('');

                        $("#bookmark-list").html(html);
                    }
                },
                error: function() {
                    $("#patent-list").html(
                        "<p class='text-red-500 text-center'>Gagal mengambil data bookmark.</p>");
                }
            });
        }

        function toggleBookmark(patentId) {
            let userId = "{{ auth()->user()->id ?? '' }}";

            console.log(userId);
            console.log(patentId);

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
                                        fetchBookmarks();
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
                },
                complete: fetchBookmarks
            });
        }

        $(document).ready(function() {
            fetchBookmarks(1);
        });
    </script>
@endsection
