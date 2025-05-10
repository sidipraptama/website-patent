@extends('layouts.app')

@section('title', 'Draft Patents')

@section('content')
    <div class="p-6 bg-white rounded-lg shadow-md">
        <!-- Result Count -->
        <p class="mt-2 text-sm font-medium text-gray-500 ml-3" id="result-count">0 drafts found</p>

        <hr class="mt-2 h-[1px] border-t-0 bg-neutral-200" />

        <!-- Grid Container -->
        <div id="draft-section">
            <!-- Loading Spinner -->
            <div id="loading-spinner" class="hidden flex items-center justify-center py-6">
                <svg class="animate-spin h-8 w-8 text-customBlue" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                    </circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                </svg>
            </div>

            <!-- Empty State -->
            <div id="empty-state" class="hidden flex justify-center items-center flex-col text-center text-gray-600 py-12">
                <img src="/images/no_data_found.svg" alt="No data" class="w-64 h-auto mb-6 opacity-90">
                <h2 class="text-xl font-semibold mb-2">No drafts found</h2>
                <p class="text-gray-500">You haven't created any draft patents yet.</p>
            </div>

            <!-- Grid Content -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mt-6" id="draft-list"></div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function fetchDrafts() {
            // Show loading spinner
            $("#loading-spinner").removeClass("hidden");

            $.ajax({
                url: "{{ route('draft-patent.getData') }}",
                type: "GET",
                success: function(response) {
                    const drafts = response || [];
                    const total = drafts.length || 0;

                    // Update result count
                    $("#result-count").text(`${total} drafts found`);

                    if (total === 0) {
                        $("#draft-list").html(""); // Kosongkan grid
                        $("#empty-state").removeClass("hidden");
                    } else {
                        const html = drafts.map(draft => `
                            <div onclick="openDetail(${draft.draft_id})" class="relative bg-white p-6 px-5 pt-5 rounded-lg shadow transition cursor-pointer hover:shadow-md h-[10rem]">
                                <div class="absolute top-5 right-4" onclick="event.stopPropagation()">
                                    <button onclick="toggleDropdown(${draft.draft_id})">
                                        <i class="fas fa-ellipsis-v fa-fw text-gray-800 hover:text-gray-600"></i>
                                    </button>
                                    <div id="dropdown-${draft.draft_id}" class="absolute right-0 mt-1 w-[10.5rem] bg-white rounded-lg shadow-lg border z-10 scale-95 opacity-0 hidden transition-all duration-200">
                                        <ul class="text-sm text-gray-700">
                                            <li>
                                                <button onclick="openDetailInNewTab(${draft.draft_id}); event.stopPropagation();" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                                                    <i class="fas fa-external-link-alt"></i> Open in new tab
                                                </button>
                                            </li>
                                            <li class="border-t border-gray-200">
                                                <button onclick="deleteItem(${draft.draft_id}); event.stopPropagation();" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <h3 class="font-semibold text-lg">${draft.title}</h3>
                                <p class="text-gray-500 mt-2 text-sm line-clamp-4">${draft.similarity_check.input_text}</p>
                            </div>
                        `).join('');

                        $("#draft-list").html(html);
                    }
                },
                error: function() {
                    // Handle error
                    $("#draft-list").html("<p class='text-red-500 text-center'>Failed to load drafts.</p>");
                },
                complete: function() {
                    // Hide loading spinner after completion
                    $("#loading-spinner").addClass("hidden");
                }
            });
        }

        function toggleDropdown(id) {
            document.querySelectorAll("[id^='dropdown-']").forEach(el => {
                if (el.id !== `dropdown-${id}`) {
                    el.classList.remove("scale-100", "opacity-100", "visible");
                    el.classList.add("scale-95", "opacity-0");
                    setTimeout(() => el.classList.add("hidden"), 200);
                }
            });

            const dropdown = document.getElementById(`dropdown-${id}`);
            const isVisible = dropdown.classList.contains("visible");

            if (isVisible) {
                // Tutup dropdown
                dropdown.classList.remove("scale-100", "opacity-100", "visible");
                dropdown.classList.add("scale-95", "opacity-0");
                setTimeout(() => dropdown.classList.add("hidden"), 200);
            } else {
                // Buka dropdown
                dropdown.classList.remove("hidden"); // <<< ini penting
                void dropdown.offsetWidth; // trigger reflow for animation
                dropdown.classList.remove("scale-95", "opacity-0");
                dropdown.classList.add("scale-100", "opacity-100", "visible");
            }
        }

        document.addEventListener('click', function(event) {
            const isInsideDropdown = event.target.closest("[id^='dropdown-']");
            const isToggleButton = event.target.closest("button[onclick^='toggleDropdown']");

            if (!isInsideDropdown && !isToggleButton) {
                document.querySelectorAll("[id^='dropdown-']").forEach(el => {
                    el.classList.remove("scale-100", "opacity-100", "visible");
                    el.classList.add("scale-95", "opacity-0");
                    setTimeout(() => el.classList.add("hidden"), 200);
                });
            }
        });

        function deleteItem(id) {
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            // Confirm delete with SweetAlert2
            Swal.fire({
                title: 'Are you sure?',
                text: 'This draft will be deleted permanently!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No, cancel!',
                customClass: {
                    popup: 'mt-[1rem]',
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Proceed with deletion
                    $.ajax({
                        url: "{{ route('draft-patent.delete', '') }}/" + id,
                        type: "DELETE",
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        success: function(response) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: response.message,
                                showConfirmButton: false,
                                timer: 3000,
                                customClass: {
                                    popup: 'mt-[1rem]',
                                }
                            });
                            fetchDrafts(); // Refresh drafts after deletion
                        },
                        error: function() {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: 'Failed to delete draft.',
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

        function duplicateItem(id) {
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{ url('draft-patent') }}/" + id + "/duplicate",
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: response.message,
                        showConfirmButton: false,
                        timer: 3000,
                        customClass: {
                            popup: 'mt-[1rem]',
                        }
                    });
                    fetchDrafts(); // Refresh drafts after duplication
                },
                error: function() {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Failed to duplicate draft.',
                        showConfirmButton: false,
                        timer: 3000,
                        customClass: {
                            popup: 'mt-[1rem]',
                        }
                    });
                }
            });
        }

        function openDetail(id) {
            const url = "{{ url('draft-patent') }}/" + id;
            window.location.href = url; // buka di tab yang sama
        }

        function openDetailInNewTab(id) {
            const url = "{{ url('draft-patent') }}/" + id;
            window.open(url, '_blank'); // buka di tab baru
        }

        // Trigger the fetchDrafts function when the document is ready
        $(document).ready(function() {
            fetchDrafts(); // This triggers the AJAX request
        });
    </script>
@endsection
