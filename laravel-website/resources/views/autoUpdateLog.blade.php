@extends('layouts.app')

@section('title', 'Auto Update Logs')

@section('content')
    <div class="p-6 bg-white rounded-lg shadow-md min-w-[32rem]">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-gray-500 ml-3" id="result-count">
                0 update logs found
            </p>

            <div class="flex items-center space-x-3 ml-auto">
                <button id="sendRabbitBtn"
                    class="bg-white hover:bg-gray-100 border border-gray-200 text-customBlue text-sm font-medium py-1.5 px-3 rounded-lg transition flex items-center space-x-2">
                    <i class="fas fa-paper-plane"></i>
                    <span>Send Update Request</span>
                </button>
            </div>
        </div>

        <hr class="mt-2 h-[1px] border-t-0 bg-neutral-200" />

        <div id="draft-section" class="mt-4 rounded overflow-auto">
            <table class="min-w-full text-sm text-left text-gray-700">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4">ID</th>
                        <th class="py-2 px-4">Status</th>
                        <th class="py-2 px-4">Started At</th>
                        <th class="py-2 px-4">Completed At</th>
                        <th class="py-2 px-4">Description</th>
                        <th class="py-2 px-4">Latest Log</th>
                        <th class="py-2 px-4">Action</th>
                    </tr>
                </thead>
                <tbody id="update-log-table" class="divide-y divide-gray-200 bg-white">
                    <!-- Fetched data will populate here -->
                </tbody>
            </table>

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
                <h2 class="text-xl font-semibold mb-2">No update logs found</h2>
                <p class="text-gray-500">System haven't updated database yet.</p>
            </div>
        </div>
    </div>

    <div id="logModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-30 flex items-center justify-center px-4">
        <div id="modalContentWrapper"
            class="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-6 relative space-y-4 max-h-[90vh] overflow-y-auto transition transform duration-300 scale-95 opacity-0">
            <!-- Close Button -->
            <button id="closeModal" class="absolute top-3 right-4 text-gray-400 hover:text-gray-600 text-2xl">
                &times;
            </button>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <h2 class="text-xl font-semibold text-gray-800">Update History Detail</h2>
                <div id="modalStatus" class="text-xs py-2">
                    <!-- Status -->
                </div>
            </div>

            <!-- Info -->
            <div class="space-y-2 text-sm text-gray-700">
                <div><span class="font-semibold">Description:</span> <span id="modalDescription"></span></div>
                <div><span class="font-semibold">Started At:</span> <span id="modalStartedAt"></span></div>
                <div><span class="font-semibold">Completed At:</span> <span id="modalCompletedAt"></span></div>
            </div>

            <hr class="border-gray-200" />

            <!-- Log Content -->
            <div>
                <h3 class="font-semibold mb-2 text-sm text-gray-800">Logs</h3>
                <div id="logContent"
                    class="bg-gray-50 rounded-lg p-3 h-60 overflow-y-auto space-y-2 border border-gray-200 text-sm text-gray-700">
                    <!-- Log items will appear here -->
                </div>
            </div>

            <!-- Footer -->
            <div class="pt-2 flex justify-end">
                <button id="cancelUpdateBtn"
                    class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-2 px-4 rounded-lg transition duration-200">
                    Cancel Update
                </button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const tableBody = document.getElementById('update-log-table');
        const resultCount = document.getElementById('result-count');
        const emptyState = document.getElementById('empty-state');
        const modal = document.getElementById('logModal');
        const modalContent = document.getElementById('logContent');
        const closeModal = document.getElementById('closeModal');
        let currentModalId = null;

        function formatDateTime(dateString) {
            if (!dateString) return '-';

            const options = {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };

            return new Date(dateString).toLocaleString('en-GB', options).replace(',', '');
        }

        function renderTable(data) {
            tableBody.innerHTML = '';

            if (!data || data.length === 0) {
                emptyState.classList.remove('hidden');
                resultCount.textContent = '0 update logs found';
                return;
            }

            emptyState.classList.add('hidden');
            resultCount.textContent = `${data.length} update logs found`;

            data.forEach(item => {
                const row = document.createElement('tr');

                const latestLog = item.update_logs && item.update_logs.length > 0 ?
                    item.update_logs[item.update_logs.length - 1].message :
                    'No logs';

                let statusBadge = '';
                switch (item.status) {
                    case 0:
                        statusBadge =
                            '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Ongoing</span>';
                        break;
                    case 1:
                        statusBadge =
                            '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Success</span>';
                        break;
                    case 2:
                        statusBadge =
                            '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Failed</span>';
                        break;
                    case 3:
                        statusBadge =
                            '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Canceled</span>';
                        break;
                    default:
                        statusBadge =
                            '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Unknown</span>';
                }

                row.classList.add('hover:bg-gray-50');
                const dataItem = encodeURIComponent(JSON.stringify(item));

                row.innerHTML = `
                    <td class="py-2 px-4">${item.update_history_id}</td>
                    <td class="py-2 px-4">${statusBadge}</td>
                    <td class="py-2 px-4">${formatDateTime(item.started_at) || '-'}</td>
                    <td class="py-2 px-4">${formatDateTime(item.completed_at) || '-'}</td>
                    <td class="py-2 px-4 text-sm">${item.description || '-'}</td>
                    <td class="py-2 px-4 text-gray-600 text-sm">${latestLog || '-'}</td>
                    <td class="py-2 px-4">
                        <button
                            data-item="${dataItem}"
                            class="view-btn ml-2 bg-white hover:bg-gray-100 border border-gray-200 text-customBlue text-xs px-4 py-2 text-semibold rounded-lg flex items-center transition-all duration-300 ease-in-out">
                            <i class="fas fa-eye text-xs text-bold mr-2"></i> View
                        </button>
                    </td>
                `;

                tableBody.appendChild(row);
            });

            tableBody.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const raw = decodeURIComponent(btn.getAttribute('data-item'));
                    const item = JSON.parse(raw);

                    fetch(`/update-history/${item.update_history_id}`)
                        .then(response => response.json())
                        .then(latestItem => {
                            currentModalId = item.update_history_id; // ✅ Simpan ID saat modal dibuka
                            showModal(latestItem);
                        })
                        .catch(error => {
                            console.error('Failed to fetch latest item details:', error);
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: 'Gagal memuat detail update',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        });
                });
            });
        }

        function showModal(item) {
            const logs = item.update_logs || [];

            const statusMap = {
                0: {
                    text: 'Ongoing',
                    bg: 'bg-yellow-100',
                    textColor: 'text-yellow-800'
                },
                1: {
                    text: 'Success',
                    bg: 'bg-green-100',
                    textColor: 'text-green-800'
                },
                2: {
                    text: 'Failed',
                    bg: 'bg-red-100',
                    textColor: 'text-red-800'
                },
                3: {
                    text: 'Canceled',
                    bg: 'bg-gray-300',
                    textColor: 'text-gray-800'
                }
            };

            const status = statusMap[item.status] || {
                text: 'Unknown',
                bg: 'bg-gray-100',
                textColor: 'text-gray-800'
            };
            const statusBadge =
                `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${status.bg} ${status.textColor}">${status.text}</span>`;

            document.getElementById('modalDescription').textContent = item.description || '-';
            document.getElementById('modalStartedAt').textContent = formatDateTime(item.started_at) || '-';
            document.getElementById('modalCompletedAt').textContent = formatDateTime(item.completed_at) || '-';
            document.getElementById('modalStatus').innerHTML = statusBadge;

            const modalContent = document.getElementById('logContent');
            modalContent.innerHTML = '';

            if (logs.length === 0) {
                modalContent.innerHTML = '<p class="text-gray-500 text-sm">No logs available.</p>';
            } else {
                logs.sort((a, b) => b.update_log_id - a.update_log_id);
                logs.forEach((log, index) => {
                    const logDiv = document.createElement('div');
                    logDiv.classList.add('px-2', 'text-sm', 'text-gray-600');
                    logDiv.textContent = `#${logs.length - index}: ${log.message}`;
                    modalContent.appendChild(logDiv);
                });
            }

            modal.classList.remove('hidden');

            const content = document.getElementById('modalContentWrapper');
            setTimeout(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);

            const cancelUpdateBtn = document.getElementById('cancelUpdateBtn');
            if (item.status === 0) {
                cancelUpdateBtn.classList.remove('hidden');
                cancelUpdateBtn.onclick = function() {
                    cancelUpdate(item.update_history_id);
                };
            } else {
                cancelUpdateBtn.classList.add('hidden');
                cancelUpdateBtn.onclick = null;
            }


        }

        function cancelUpdate(historyUpdateId) {
            Swal.fire({
                title: 'Batalkan update ini?',
                text: 'Tindakan ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, batalkan',
                cancelButtonText: 'Tidak',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route('update.cancel') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({
                                history_update_id: historyUpdateId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'success',
                                    title: 'Update berhasil dibatalkan',
                                    showConfirmButton: false,
                                    timer: 3000
                                });
                                modal.classList.add('hidden');
                                currentModalId = null; // ❌ Reset ID saat modal ditutup
                                fetchUpdateHistory();
                            } else {
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'error',
                                    title: 'Gagal membatalkan update',
                                    showConfirmButton: false,
                                    timer: 3000
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error cancelling update:', error);
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: 'Terjadi kesalahan',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        });
                }
            });
        }

        closeModal.addEventListener('click', () => {
            const content = document.getElementById('modalContentWrapper');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                currentModalId = null;
            }, 200); // waktu animasi sesuai Tailwind transition
        });

        modal.addEventListener('click', (event) => {
            const content = document.getElementById('modalContentWrapper');
            if (!content.contains(event.target)) {
                modal.classList.add('hidden');
                content.classList.remove('scale-100', 'opacity-100');
                content.classList.add('scale-95', 'opacity-0');
                currentModalId = null;
            }
        });

        const fetchUpdateHistory = (showSpinner = false) => {
            if (showSpinner) {
                $("#loading-spinner").removeClass("hidden");
            }

            fetch('{{ route('fetch-update-history') }}')
                .then(response => response.json())
                .then(data => {
                    renderTable(data);
                })
                .catch(error => {
                    console.error('Error fetching update logs:', error);
                    if (typeof resultCount !== 'undefined') {
                        resultCount.textContent = 'Failed to load update logs';
                    }
                })
                .finally(() => {
                    if (showSpinner) {
                        $("#loading-spinner").addClass("hidden");
                    }
                });
        };

        // 🔁 Fetch data awal dan setup interval
        document.addEventListener('DOMContentLoaded', () => {
            fetchUpdateHistory(true);

            setInterval(() => {
                fetchUpdateHistory(false);
                if (currentModalId !== null) {
                    fetch(`/update-history/${currentModalId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Data not found or has been deleted');
                            }
                            return response.json();
                        })
                        .then(item => {
                            showModal(item); // 🔁 Update isi modal
                        })
                        .catch(error => {
                            console.warn('Modal data no longer available:', error);
                            modal.classList.add('hidden'); // ❌ Tutup modal
                            currentModalId = null; // 🔄 Reset ID
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'info',
                                title: 'Update sudah tidak tersedia',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        });
                }
            }, 2000);
        });

        // Fetch data on first load with spinner
        document.addEventListener('DOMContentLoaded', () => {
            fetchUpdateHistory(true); // Show spinner

            // Auto refresh tiap 10 detik tanpa spinner
            setInterval(() => fetchUpdateHistory(false), 10000);
        });

        // Send to RabbitMQ
        document.getElementById('sendRabbitBtn').addEventListener('click', function() {
            fetch('{{ route('send.rabbit') }}')
                .then(response => response.json())
                .then(data => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: data.message || 'Berhasil dikirim ke RabbitMQ!',
                        showConfirmButton: false,
                        timer: 3000,
                        customClass: {
                            popup: 'mt-[1rem]'
                        }
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Gagal mengirim ke RabbitMQ!',
                        showConfirmButton: false,
                        timer: 3000,
                        customClass: {
                            popup: 'mt-[1rem]'
                        }
                    });
                });
        });
    </script>
@endsection
