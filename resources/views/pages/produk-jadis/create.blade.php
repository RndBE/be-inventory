
<button data-modal-target="tambahprodukjadis-modal" data-modal-toggle="tambahprodukjadis-modal" class="mt-2 block w-fit rounded-md py-1.5 px-2 bg-indigo-600 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600" type="button">Tambah</button>


<!-- Main modal -->
<div id="tambahprodukjadis-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-full max-h-full" style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Tambah Produk Jadi
                </h3>
                <button type="button" class="end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="tambahprodukjadis-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <div class="pt-0 p-5">
                <form class="space-y-6" method="POST" action="{{ route('produk-jadis.store') }}" enctype="multipart/form-data">
                    @csrf
                    <!-- Nama Produk -->
                    <div>
                        <label for="nama_produk" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Product Number
                        </label>
                        <input
                            autofocus
                            type="text"
                            name="nama_produk"
                            id="nama_produk"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                                focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5
                                dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                            placeholder="Masukkan Product Number"
                        >
                    </div>

                    <!-- Sub Solusi -->
                    <div>
                        <label for="sub_solusi" class="block text-sm font-medium text-gray-900 dark:text-white">
                            Sub Solusi
                        </label>
                        <select
                            id="sub_solusi"
                            name="sub_solusi"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                                focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5
                                dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400
                                dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        >
                            <option selected disabled>Pilih Sub Solusi</option>
                            <option value="Automatic Water Level Recorder">Automatic Water Level Recorder</option>
                            <option value="Automatic Water Gate Controller">Automatic Water Gate Controller</option>
                            <option value="Automatic Flow Meter Recorder">Automatic Flow Meter Recorder</option>
                            <option value="Automatic Deformation Recorder">Automatic Deformation Recorder</option>
                            <option value="Automatic Water Quality Recorder">Automatic Water Quality Recorder</option>
                            <option value="Automatic Vibrating Wire Recorder">Automatic Vibrating Wire Recorder</option>
                            <option value="Automatic Rain Recorder">Automatic Rain Recorder</option>
                            <option value="Automatic Weather Recorder">Automatic Weather Recorder</option>
                            <option value="Early Warning System">Early Warning System</option>
                            <option value="Automatic Pressure Level Recorder">Automatic Pressure Level Recorder</option>
                        </select>
                    </div>

                    {{-- <div>
                        <label for="kode_bahan" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Produk Nomor
                        </label>
                        <input
                            autofocus
                            type="text"
                            name="kode_bahan"
                            id="kode_bahan"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                                focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5
                                dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                            placeholder="Masukkan Produk Nomor"
                        >
                    </div> --}}

                    <!-- Upload File -->
                    <div>
                        <label class="block mb-2 mt-2 text-sm font-medium text-gray-900 dark:text-white" for="file_input">
                            Upload file
                        </label>
                        <input
                            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer
                                bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700
                                dark:border-gray-600 dark:placeholder-gray-400"
                            aria-describedby="file_input_help"
                            id="file_input"
                            type="file"
                            name="gambar"
                            accept=".jpg,.jpeg,.png,.gif,.svg,image/*"
                        >
                        <p class="mt-2 mb-2 text-sm text-gray-500 dark:text-gray-300" id="file_input_help">
                            SVG, PNG, JPG atau GIF (MAX. 800x400px).
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full text-white bg-indigo-600 hover:bg-indigo-800 focus:ring-4 focus:outline-none
                            focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center
                            dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800"
                    >
                        Simpan
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>
