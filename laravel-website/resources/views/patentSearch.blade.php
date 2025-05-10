@extends('layouts.app')

@section('title', 'Patent Search')

@section('content')
    <div class="p-6 pt-8 bg-white rounded-lg shadow-md min-w-[32rem]">
        <!-- Search Bar -->
        <div class="flex items-center justify-between gap-2">
            <div class="relative w-full max-w-lg">
                <input type="text" id="search" placeholder="Search patents..."
                    class="w-full pl-10 pr-4 py-2 border bg-white border-white rounded-lg text-slate-900/70 ring-[0.5px] ring-slate-300 focus:ring-customBlue focus:text-slate-900 focus:outline-none transition-all duration-300 ease-in-out">
                <span class="absolute inset-y-0 left-3 flex items-center">
                    <i class="fas fa-search fa-fw text-slate-900 flex-shrink-0"></i>
                </span>
            </div>

            <!-- Filter & Sorting -->
            <div class="flex">
                <div class="max-w-sm mx-auto relative">
                    <select id="sortBy"
                        class="appearance-none bg-white border border-white text-sm rounded-lg text-slate-900/70 ring-[0.5px] ring-slate-300 focus:ring-customBlue block w-full p-2.5 pr-8 transition-all duration-300 ease-in-out">
                        <option value="relevance">Sort by: Relevance</option>
                        <option value="newest">Sort by: Newest</option>
                        <option value="oldest">Sort by: Oldest</option>
                    </select>
                    <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-slate-900 text-xs"></i>
                    </span>
                </div>
            </div>
        </div>

        <!-- Result Count -->
        <p class="mt-5 text-sm font-medium text-slate-900/70 ml-3" id="result-count">0 patents found</p>

        <hr class="mt-2 h-[1px] border-t-0 bg-neutral-200" />

        <!-- Patent List (Grid) -->
        <div class="grid grid-cols-1 mt-6" id="patent-list">
            <div id="loading-spinner" class="hidden flex items-center justify-center py-6">
                <svg class="animate-spin h-8 w-8 text-customBlue" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                    </circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                </svg>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex justify-end" id="pagination"></div>
    </div>
@endsection

@section('scripts')
    <script>
        let currentPage = 1;
        let totalPages = 1;

        function fetchPatents(query = '', page = 1, sortBy = 'relevance') {
            $("#loading-spinner").removeClass("hidden");

            $.ajax({
                url: "{{ route('patent.search') }}",
                type: "GET",
                data: {
                    query: query,
                    page: page,
                    size: 12,
                    sort_by: sortBy
                },
                success: function(response) {
                    let results = response.results || [];
                    let searchQuery = $("#search").val().trim();

                    let totalText = response.total >= 10000 ?
                        "10,000+ patents found" :
                        `${response.total.toLocaleString()} patents found`;
                    $("#result-count").text(totalText);

                    totalPages = Math.ceil(response.total / 12);

                    if (response.total >= 10_000) {
                        totalPages = totalPages - 1;
                    }

                    if (results.length === 0) {
                        $("#patent-list").html(`
                        <div class="flex flex-col items-center justify-center py-12 text-center text-gray-600">
                            <img src="/images/no_data_found.svg" alt="No data" class="w-64 h-auto mb-6 opacity-90">
                            <h2 class="text-xl font-semibold mb-2">No patents found</h2>
                            <p class="text-gray-500">Try using different keywords or check your search again.</p>
                        </div>
                    `);
                    } else {
                        let html = results.map(result => {
                            let patent = result._source;
                            let highlightedTitle = highlightText(patent.patent_title, searchQuery);
                            let highlightedAbstract = highlightText(patent.patent_abstract,
                                searchQuery);

                            return `
                            <div class="relative bg-white p-4 cursor-pointer transition duration-300" onclick="window.open('https://patents.google.com/patent/US${patent.patent_id}', '_blank')"">
                                <button class="absolute top-4 right-4 text-gray-500" onclick="event.stopPropagation(); toggleBookmark('${patent.patent_id}');">
                                    <i class="fas fa-bookmark text-lg transition-all duration-300 ease-in-out ${patent.is_bookmarked ? 'text-yellow-400 hover:text-yellow-300' : 'text-gray-400 hover:text-gray-200'}" id="bookmark-icon-${patent.patent_id}"></i>
                                </button>
                                <h3 class="font-semibold text-lg mr-8">${highlightedTitle}</h3>
                                <div class="flex text-gray-700 mt-1 text-xs">
                                    <span>ID: ${patent.patent_id}</span>
                                    <span class="ml-4">Granted: ${patent.patent_date}</span>
                                    <span class="ml-4">Type: ${patent.patent_type}</span>
                                </div>
                                <p class="text-gray-500 text-sm mt-2">${highlightedAbstract}</p>
                                <hr class="mt-6 border-t border-gray-200" />
                            </div>
                        `;
                        }).join('');

                        $("#patent-list").html(html);
                    }

                    updatePagination();
                },
                error: function() {
                    $("#patent-list").html(
                        "<p class='text-red-500 text-center'>Gagal mengambil data paten.</p>");
                },
                complete: function() {
                    $("#loading-spinner").addClass("hidden");
                }
            });
        }

        // Function to update pagination
        function updatePagination() {
            let paginationHtml = '';

            // Previous button
            if (currentPage > 1) {
                paginationHtml += `<a href="#" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-white rounded-md hover:bg-gray-100 hover:text-gray-700" onclick="changePage(${currentPage - 1})">
                    <i class="fas fa-chevron-left"></i>
                </a>`;
            }

            // Page numbers
            let pageNumbersToShow = 3; // Number of pages to display
            let startPage = Math.max(1, currentPage - Math.floor(pageNumbersToShow / 2));
            let endPage = Math.min(totalPages, startPage + pageNumbersToShow - 1);

            // Adjust startPage to ensure there's room for the previous pages if needed
            if (endPage - startPage < pageNumbersToShow - 1) {
                startPage = Math.max(1, endPage - pageNumbersToShow + 1);
            }

            // First page
            if (startPage > 1) {
                paginationHtml +=
                    `<a href="#" class="flex rounded-md items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-white hover:bg-gray-100 hover:text-gray-700" onclick="changePage(1)">1</a>`;
            }

            // Page numbers between start and end
            for (let i = startPage; i <= endPage; i++) {
                paginationHtml +=
                    `<a href="#" class="flex rounded-md items-center justify-center px-3 h-8 leading-tight ${i === currentPage ? 'bg-customBlue text-white hover:bg-customBlue-hover' : 'bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-700'} border border-white" onclick="changePage(${i})">${i}</a>`;
            }

            // Ellipsis if there are more pages after
            if (endPage < totalPages - 1) {
                paginationHtml +=
                    `<span class="flex rounded-md items-center justify-center px-3 h-8 leading-tight text-gray-500">...</span>`;
            }

            // Last page
            if (endPage < totalPages) {
                paginationHtml +=
                    `<a href="#" class="flex rounded-md items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-white hover:bg-gray-100 hover:text-gray-700" onclick="changePage(${totalPages})">${totalPages}</a>`;
            }

            // Next button
            if (currentPage < totalPages) {
                paginationHtml += `<a href="#" class="flex rounded-md items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-white rounded-md hover:bg-gray-100 hover:text-gray-700" onclick="changePage(${currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>`;
            }

            // Update pagination UI
            $("#pagination").html(paginationHtml);
        }

        // Change page
        function changePage(page) {
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            fetchPatents($("#search").val(), currentPage, $("#sortBy").val());
        }

        $("#sortBy").on("change", function() {
            fetchPatents($("#search").val(), 1, $(this).val()); // Reload patents with sorting option
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

        function highlightText(text, query) {
            if (!query) return text; // Jika tidak ada query, kembalikan teks asli

            let words = query.trim().split(/\s+/); // Pisahkan query menjadi kata-kata
            let regex = new RegExp(`\\b(${words.join("|")})\\b`, "gi"); // Buat regex untuk mencocokkan kata utuh

            return text.replace(regex, `<mark class="bg-yellow-200">$1</mark>`); // Tambahkan highlight
        }

        $(document).ready(function() {
            // Trigger search when typing
            $("#search").on("input", function() {
                let query = $(this).val().trim();

                if (query.length < 3) {
                    fetchPatents('', 1, $("#sortBy").val()); // Fetch all patents if query is empty
                } else {
                    fetchPatents(query, 1, $("#sortBy").val()); // Fetch patents based on search query
                }
            });

            // Initial fetch with empty query to load all patents on first load
            fetchPatents('', 1, $("#sortBy").val()); // Fetch all patents initially
        });
    </script>
@endsection
