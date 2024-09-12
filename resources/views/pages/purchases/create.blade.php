@section('title', 'Tambah Bahan Masuk | BE INVENTORY')
<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Other content -->

        <div class="w-full bg-white border border-gray-200 rounded-lg shadow sm:p-2 dark:bg-gray-800 dark:border-gray-700 mb-4">
            <livewire:search-bahan />
        </div>

        <div class="w-full p-6 bg-white border border-gray-200 rounded-lg shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
            <form>
                <div class="space-y-6">
                    <div class="border-b border-gray-900/10 pb-2">
                        <div class="mt-1 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

                            <div class="sm:col-span-2 sm:col-start-1">
                            <label for="city" class="block text-sm font-medium leading-6 text-gray-900">Kode Barang Masuk</label>
                            <div class="mt-2">
                                <input type="text" name="kode_bahan" id="kode_bahan" value="KBM - " disabled class="block w-full rounded-md border-gray-300 bg-gray-100 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            </div>
                            </div>

                            <div class="sm:col-span-2">

                            </div>

                            <div class="sm:col-span-2">
                                <label for="postal-code" class="block text-sm font-medium leading-6 text-gray-900">Tanggal Masuk</label>
                                <div class="mt-2">
                                    <div class="relative max-w-sm">
                                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                            <svg class="w-3 h-3 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                            </svg>
                                        </div>
                                        <input id="datepicker-autohide" datepicker datepicker-autohide type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 py-1.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Select date">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Table Section -->
                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-0">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400" id="selected-bahan-table">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Bahan</th>
                                    <th scope="col" class="px-6 py-3">Unit Price</th>
                                    <th scope="col" class="px-6 py-3">Stok</th>
                                    <th scope="col" class="px-6 py-3">Qty</th>
                                    <th scope="col" class="px-6 py-3">Sub Total</th>
                                    <th scope="col" class="px-6 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody id="bahan-table-body">
                                <!-- Items will be dynamically added here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="border-b border-gray-900/10 pb-2">
                        <div class="mt-1 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

                            <div class="sm:col-span-2 sm:col-start-1">

                            </div>

                            <div class="sm:col-span-2">

                            </div>

                            <div class="sm:col-span-2">
                                <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                        <tbody>
                                            <tr class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700">
                                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                                    Total Harga
                                                </th>
                                                <td class="px-6 py-4">
                                                    = Rp. 0
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>

        <!-- JavaScript to handle event -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Livewire.on('bahanSelected', bahan => {
                    const tableBody = document.getElementById('bahan-table-body');

                    // Create a new row
                    const row = document.createElement('tr');
                    row.classList.add('bg-white', 'border-b', 'dark:bg-gray-800', 'dark:border-gray-700', 'hover:bg-gray-50', 'dark:hover:bg-gray-600');

                    // Fill the row with bahan data
                    row.innerHTML = `
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">${bahan.nama_bahan}</td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">Rp. ${bahan.unit_price}</td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">${bahan.total_stok} Pcs</td>
                        <td class="px-6 py-4">
                            <input type="number" value="1" class="bg-gray-50 w-14 border border-gray-300 text-gray-900 text-sm rounded-lg px-2.5 py-1" required>
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">Rp. ${bahan.unit_price}</td>
                        <td class="px-6 py-4">
                            <button type="button" class="text-red-600 hover:underline" onclick="removeRow(this)"><svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg></button>
                        </td>
                    `;

                    // Append the new row to the table
                    tableBody.appendChild(row);
                });
            });

            function removeRow(button) {
                const row = button.closest('tr');
                row.remove();
            }
        </script>
    </div>
</x-app-layout>
