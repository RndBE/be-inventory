<?php

use App\Helpers\LogHelper;
use App\Livewire\Quality\QcWizard;
use App\Exports\LaporanProyekExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Route;
use App\Livewire\Quality\QcBahanMasuk;
use App\Http\Controllers\JobController;
use Illuminate\Support\Facades\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BahanController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProjekController;
use App\Livewire\Quality\QcBahanMasukView;
use App\Livewire\Quality\QcProdukJadiView;
use App\Exports\LaporanGaransiProyekExport;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KontrakController;
use App\Http\Controllers\StokRndController;
use App\Livewire\Quality\QcBahanMasukTable;
use App\Livewire\Quality\QcProdukJadiTable;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DataFeedController;
use App\Http\Controllers\ProduksiController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SupplierController;
use App\Livewire\Quality\QcProdukJadiWizard;
use App\Http\Controllers\BahanJadiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\ProjekRndController;
use App\Http\Controllers\RekapAsetController;
use App\Http\Controllers\BahanReturController;
use App\Http\Controllers\BahanRusakController;
use App\Http\Controllers\BarangAsetController;
use App\Http\Controllers\JenisBahanController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProdukJadiController;
use App\Http\Controllers\UploadTempController;
use App\Http\Controllers\BahanKeluarController;
use App\Http\Controllers\JobPositionController;
use App\Http\Controllers\LogActivityController;
use App\Http\Controllers\ProdukJadisController;
use App\Http\Controllers\QualityPageController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProdukSampleController;
use App\Http\Controllers\QCBahanMasukController;
use App\Http\Controllers\StokProduksiController;
use App\Http\Controllers\GaransiProjekController;
use App\Http\Controllers\LaporanProyekController;
use App\Http\Controllers\PerbaikanDataController;
use App\Http\Controllers\InventoryTokenController;
use App\Http\Controllers\PembelianBahanController;
use App\Http\Controllers\ProdukProduksiController;
use App\Livewire\Quality\QcProdukSetengahJadiView;
use App\Livewire\Quality\QcProdukSetengahJadiTable;
use App\Http\Controllers\Api\QcProdukJadiController;
use App\Http\Controllers\PengambilanBahanController;
use App\Livewire\Quality\QcProdukSetengahJadiWizard;
use App\Http\Controllers\BahanSetengahjadiController;
use App\Http\Controllers\PengajuanPembelianController;
use App\Http\Controllers\ProduksiProdukJadiController;
use App\Http\Controllers\LaporanGaransiProyekController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/login');
});
Route::get('/register', function () {
    abort(404);
});

Route::post('/login', [AuthController::class, 'login']);
Route::get('/notif-transaksi', [PurchaseController::class, 'notifTransaksi']);
// web.php
Route::get('/auto-login/{token}', [AuthController::class, 'autoLogin']);




Route::middleware(['auth:sanctum', 'verified', 'isAdmin'])->group(function () {

    Route::resource('log-activities', LogActivityController::class);
    Route::resource('permissions', PermissionController::class);
    Route::resource('roles', RoleController::class);
    Route::get('roles/{roleId}/give-permissions', [RoleController::class, 'addPermissionToRole'])->name('roles.add-permissions');
    Route::put('roles/{roleId}/give-permissions', [RoleController::class, 'givePermissionToRole'])->name('roles.give-permissions');

    Route::resource('users', UserController::class);
    // Route::get('/json-data-feed', [DataFeedController::class, 'getDataFeed'])->name('json_data_feed');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/bahan/edit-multiple', [BahanController::class, 'editMultiple'])->name('bahan.editmultiple');
    Route::put('/bahan/update-multiple', [BahanController::class, 'updateMultiple'])->name('bahan.update.multiple');

    Route::get('/bahan', [BahanController::class, 'index'])->name('bahan.index');
    Route::get('/bahan/create', [BahanController::class, 'create'])->name('bahan.create');
    Route::post('/bahan/store', [BahanController::class, 'store'])->name('bahan.store');
    Route::get('/bahan/{id}/edit', [BahanController::class, 'edit'])->name('bahan.edit');
    Route::put('/bahan/{id}', [BahanController::class, 'update'])->name('bahan.update');
    Route::delete('/bahan/{id}', [BahanController::class, 'destroy'])->name('bahan.destroy');
    Route::get('bahan-export', [BahanController::class, 'export'])->name('bahan.export');

    Route::resource('barang-aset', BarangAsetController::class);
    Route::resource('rekap-aset', RekapAsetController::class);
    Route::post('rekap-aset/import', [RekapAsetController::class, 'import'])->name('rekap-aset.import');

    Route::resource('supplier', SupplierController::class);
    Route::get('supplier-export', [SupplierController::class, 'export'])->name('supplier.export');
    Route::resource('jenis-bahan', JenisBahanController::class);
    Route::get('jenisbahan-expot', [JenisBahanController::class, 'export'])->name('jenisbahan-expot.export');
    Route::resource('unit', UnitController::class);
    Route::get('unit-export', [UnitController::class, 'export'])->name('unit.export');
    Route::resource('purchases', PurchaseController::class);
    Route::get('purchases-export', [PurchaseController::class, 'export'])->name('purchases-export.export');
    Route::resource('kontrak', KontrakController::class);

    Route::resource('organization', OrganizationController::class);
    Route::resource('job-position', JobPositionController::class);

    Route::get('/bahan-keluars/pdf/{id}', [BahanKeluarController::class, 'downloadPdf'])->name('bahan-keluars.downloadPdf');
    Route::resource('bahan-keluars', BahanKeluarController::class);
    Route::put('/bahan-keluars/updateApprovalLeader/{id}', [BahanKeluarController::class, 'updateApprovalLeader'])->name('bahan-keluars.updateApprovalLeader');
    Route::put('bahan-keluars/{id}/updatepengambilan', [BahanKeluarController::class, 'updatepengambilan'])->name('bahan-keluars.updatepengambilan');
    Route::post('/siap-ambil/{id}', [BahanKeluarController::class, 'sendWhatsApp'])->name('send.siap-ambil');

    Route::get('/pengajuan-pembelian-bahan/pdf/{id}', [PembelianBahanController::class, 'downloadPdf'])->name('pengajuan-pembelian-bahan.downloadPdf');
    Route::get('/pengajuan-pembelian-bahan/pdf_po/{id}', [PembelianBahanController::class, 'downloadPdfPo'])->name('pengajuan-pembelian-bahan.downloadPdfPo');
    Route::resource('pengajuan-pembelian-bahan', PembelianBahanController::class);
    Route::put('/pengajuan-pembelian-bahan/updateApprovalLeader/{id}', [PembelianBahanController::class, 'updateApprovalLeader'])->name('pengajuan-pembelian-bahan.updateApprovalLeader');
    Route::put('/pengajuan-pembelian-bahan/updateApprovalGM/{id}', [PembelianBahanController::class, 'updateApprovalGM'])->name('pengajuan-pembelian-bahan.updateApprovalGM');
    Route::put('/pengajuan-pembelian-bahan/updateApprovalManager/{id}', [PembelianBahanController::class, 'updateApprovalManager'])->name('pengajuan-pembelian-bahan.updateApprovalManager');
    Route::put('/pengajuan-pembelian-bahan/updateApprovalPurchasing/{id}', [PembelianBahanController::class, 'updateApprovalPurchasing'])->name('pengajuan-pembelian-bahan.updateApprovalPurchasing');
    Route::put('/pengajuan-pembelian-bahan/updateApprovalFinance/{id}', [PembelianBahanController::class, 'updateApprovalFinance'])->name('pengajuan-pembelian-bahan.updateApprovalFinance');
    Route::put('/pengajuan-pembelian-bahan/updateApprovalAdminManager/{id}', [PembelianBahanController::class, 'updateApprovalAdminManager'])->name('pengajuan-pembelian-bahan.updateApprovalAdminManager');
    Route::put('/pengajuan-pembelian-bahan/updateApprovalDirektur/{id}', [PembelianBahanController::class, 'updateApprovalDirektur'])->name('pengajuan-pembelian-bahan.updateApprovalDirektur');
    Route::get('/pengajuan-pembelian-bahan/{id}/editHarga', [PembelianBahanController::class, 'editHarga'])
    ->name('pengajuan-pembelian-bahan.editHarga');
    Route::put('/pengajuan-pembelian-bahan/{id}/updateHarga', [PembelianBahanController::class, 'updateHarga'])->name('pengajuan-pembelian-bahan.updateHarga');
    Route::put('/pengajuan-pembelian-bahan/uploadInvoicePembelian/{id}', [PembelianBahanController::class, 'uploadInvoice'])->name('pengajuan-pembelian-bahan.uploadInvoicePembelian');
    Route::put('/pengajuan-pembelian-bahan/uploadDokumenPembelian/{id}', [PembelianBahanController::class, 'uploadDokumen'])->name('pengajuan-pembelian-bahan.uploadDokumenPembelian');
    // Route::put('pengajuan-pembelian-bahan/{id}/updatepengambilan', [PembelianBahanController::class, 'updatepengambilan'])->name('pengajuan-pembelian-bahan.updatepengambilan');
    // Route::post('/siap-ambil/{id}', [BahanKeluarController::class, 'sendWhatsApp'])->name('send.siap-ambil');
    Route::get('pembelian-bahan-export', [PembelianBahanController::class, 'export'])->name('pembelian-bahan-export.export');

    Route::get('/pengajuan-pembelian/pdf/{id}', [PengajuanPembelianController::class, 'downloadPdf'])->name('pengajuan-pembelian.downloadPdf');
    Route::get('/pengajuan-pembelian/pdf_po/{id}', [PengajuanPembelianController::class, 'downloadPdfPo'])->name('pengajuan-pembelian.downloadPdfPo');
    Route::resource('pengajuan-pembelian', PengajuanPembelianController::class);

    Route::resource('pengajuans', PengajuanController::class);
    Route::put('pengajuans/{pengajuan}/selesai', [PengajuanController::class, 'updateStatus'])->name('pengajuans.updateStatus');

    Route::resource('produksis', ProduksiController::class);
    Route::put('produksis/{produksi}/selesai', [ProduksiController::class, 'updateStatus'])->name('produksis.updateStatus');
    Route::get('produksis-export/{produksi_id}', [ProduksiController::class, 'export'])->name('produksis.export');
    Route::get('produksis/{produksi}/info', [ProduksiController::class, 'info'])->name('produksis.info');

    Route::resource('produksi-produk-jadi', ProduksiProdukJadiController::class);
    Route::put('produksi-produk-jadi/{produksi}/selesai', [ProduksiProdukJadiController::class, 'updateStatus'])->name('produksi-produk-jadi.updateStatus');
    Route::get('produksi-produk-jadi/{produksi}/info', [ProduksiProdukJadiController::class, 'info'])->name('produksi-produk-jadi.info');

    Route::resource('projeks', ProjekController::class);
    Route::put('projeks/{projek}/selesai', [ProjekController::class, 'updateStatus'])->name('projeks.updateStatus');
    Route::get('projeks-export/{projek_id}', [ProjekController::class, 'export'])->name('projeks.export');
    Route::get('projeks/{projek}/info', [ProjekController::class, 'info'])->name('projeks.info');

    Route::resource('produk-sample', ProdukSampleController::class);
    Route::put('produk-sample/{produk}/selesai', [ProdukSampleController::class, 'updateStatus'])->name('produk-sample.updateStatus');
    Route::get('produk-sample-export/{projek_id}', [ProdukSampleController::class, 'export'])->name('produk-sample.export');
    Route::get('produk-sample/{produk}/info', [ProdukSampleController::class, 'info'])->name('produk-sample.info');

    Route::resource('garansi-projeks', GaransiProjekController::class);
    Route::put('garansi-projeks/{projek}/selesai', [GaransiProjekController::class, 'updateStatus'])->name('garansi-projeks.updateStatus');
    Route::get('garansi-projeks-export/{projek_id}', [GaransiProjekController::class, 'export'])->name('garansi-projeks.export');
    Route::get('garansi-projeks/{projek}/info', [GaransiProjekController::class, 'info'])->name('garansi-projeks.info');

    Route::resource('bahan-rusaks', BahanRusakController::class);
    Route::get('/bahan-rusaks/pdf/{id}', [BahanRusakController::class, 'downloadPdf'])->name('bahan-rusaks.downloadPdf');

    Route::resource('bahan-setengahjadis', BahanSetengahjadiController::class);
    Route::get('bahan-setengahjadis-export', [BahanSetengahjadiController::class, 'export'])->name('bahan-setengahjadis-export.export');
    Route::resource('produk-jadi', ProdukJadisController::class);

    Route::resource('produk-produksis', ProdukProduksiController::class);
    Route::get('/produk-produksis/pdf/{id}', [ProdukProduksiController::class, 'downloadPdf'])->name('produk-produksis.downloadPdf');
    Route::get('/produk-produksis/pdfmodal/{id}', [ProdukProduksiController::class, 'downloadPdfmodal'])->name('produk-produksis.downloadPdfmodal');
    Route::resource('bahan-returs', BahanReturController::class);
    Route::get('/bahan-returs/pdf/{id}', [BahanReturController::class, 'downloadPdf'])->name('bahan-returs.downloadPdf');

    Route::resource('produk-jadis', ProdukJadiController::class);

    Route::resource('stock-opname', StockOpnameController::class);
    Route::put('/stock-opname/updateApprovalFinance/{id}', [StockOpnameController::class, 'updateApprovalFinance'])->name('stock-opname.updateApprovalFinance');
    Route::put('/stock-opname/updateApprovalDirektur/{id}', [StockOpnameController::class, 'updateApprovalDirektur'])->name('stock-opname.updateApprovalDirektur');
    Route::put('/stock-opname/selesaiStockOpname/{id}', [StockOpnameController::class, 'selesaiStockOpname'])->name('stock-opname.selesaiStockOpname');
    Route::get('/stock-opnam/pdf/{id}', [StockOpnameController::class, 'downloadPdf'])->name('stock-opname.downloadPdf');

    Route::resource('projek-rnd', ProjekRndController::class);
    Route::put('projek-rnd/{projek}/selesai', [ProjekRndController::class, 'updateStatus'])->name('projek-rnd.updateStatus');
    Route::get('projek-rnd-export/{projek_rnd_id}', [ProjekRndController::class, 'export'])->name('projek-rnd.export');
    Route::get('projek-rnd/{projek}/info', [ProjekRndController::class, 'info'])->name('projek-rnd.info');


    Route::resource('pengambilan-bahan', PengambilanBahanController::class);
    Route::put('pengambilan-bahan/{pengajuan}/selesai', [PengambilanBahanController::class, 'updateStatus'])->name('pengambilan-bahan.updateStatus');


    Route::resource('laporan-proyek', LaporanProyekController::class);
    Route::get('/laporan-proyek/export/{projekId}', function ($projekId) {
        return Excel::download(new LaporanProyekExport($projekId), 'LaporanProyek.xlsx');
    })->name('laporan-proyek.export');

    Route::resource('laporan-garansi-proyek', LaporanGaransiProyekController::class);
    Route::get('/laporan-garansi-proyek/export/{garansi_proyek_id}', function ($garansi_proyek_id) {
        return Excel::download(new LaporanGaransiProyekExport($garansi_proyek_id), 'LaporanGaransiProyek.xlsx');
    })->name('laporan-garansi-proyek.export');

    Route::resource('perbaikan-data', PerbaikanDataController::class);
    Route::put('/perbaikan-data/updateApproval/{id}', [PerbaikanDataController::class, 'updateApproval'])->name('perbaikan-data.updateApproval');

    Route::prefix('quality-page')->name('quality-page.')->group(function () {
        Route::get('/', [QualityPageController::class, 'index'])->name('index');

        Route::get('qc-bahan-masuk', QcBahanMasukTable::class)->name('qc-bahan-masuk.index');
        Route::get('qc-bahan-masuk/create', QcWizard::class)->middleware('permission:tambah-qc-bahan-masuk')->name('qc-bahan-masuk.wizard');
        Route::get('qc-bahan-masuk/view/{id_qc_bahan_masuk}', QcBahanMasukView::class)->name('qc-bahan-masuk.view');

        Route::get('qc-produk-setengah-jadi', QcProdukSetengahJadiTable::class)->name('qc-produk-setengah-jadi.index');
        Route::get('qc-produk-setengah-jadi/create', QcProdukSetengahJadiWizard::class)->middleware('permission:tambah-qc-produk-setengahjadi')->name('qc-produk-setengah-jadi.wizard');
        Route::get('qc-produk-setengah-jadi/view/{id}', QcProdukSetengahJadiView::class)->name('qc-produk-setengah-jadi.view');

        Route::get('qc-produk-jadi', QcProdukJadiTable::class)->name('qc-produk-jadi.index');
        Route::get('qc-produk-jadi/create', QcProdukJadiWizard::class)->middleware('permission:tambah-qc-produk-jadi')->name('qc-produk-jadi.wizard');
        Route::get('qc-produk-jadi/view/{id}', QcProdukJadiView::class)->name('qc-produk-jadi.view');
    });

    Route::get('/qc-produk-jadi', [QcProdukJadiController::class, 'index']);
    Route::get('/qc-produk-jadi/{id}', [QcProdukJadiController::class, 'show']);

    // Route::post('/inventory-token/generate/{user}', [InventoryTokenController::class, 'generate'])->name('admin.inventory-token.generate');

    Route::fallback(function() {
        return view('pages/utility/404');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});


