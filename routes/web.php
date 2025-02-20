<?php

use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KontrakController;
use App\Http\Controllers\StokRndController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DataFeedController;
use App\Http\Controllers\ProduksiController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SupplierController;
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
use App\Http\Controllers\BahanKeluarController;
use App\Http\Controllers\JobPositionController;
use App\Http\Controllers\LogActivityController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\StokProduksiController;
use App\Http\Controllers\PembelianBahanController;
use App\Http\Controllers\ProdukProduksiController;
use App\Http\Controllers\PengambilanBahanController;
use App\Http\Controllers\BahanSetengahjadiController;

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

Route::get('/forgot-password', function () {
    abort(404);
});

Route::post('/login', [AuthController::class, 'login']);
Route::get('/notif-transaksi', [PurchaseController::class, 'notifTransaksi']);




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
    // Route::put('pengajuan-pembelian-bahan/{id}/updatepengambilan', [PembelianBahanController::class, 'updatepengambilan'])->name('pengajuan-pembelian-bahan.updatepengambilan');
    // Route::post('/siap-ambil/{id}', [BahanKeluarController::class, 'sendWhatsApp'])->name('send.siap-ambil');
    Route::get('pembelian-bahan-export', [PembelianBahanController::class, 'export'])->name('pembelian-bahan-export.export');

    Route::resource('pengajuans', PengajuanController::class)->middleware('check.time.access');
    Route::put('pengajuans/{pengajuan}/selesai', [PengajuanController::class, 'updateStatus'])->name('pengajuans.updateStatus');

    Route::resource('produksis', ProduksiController::class);
    Route::put('produksis/{produksi}/selesai', [ProduksiController::class, 'updateStatus'])->name('produksis.updateStatus');
    Route::get('produksis-export/{produksi_id}', [ProduksiController::class, 'export'])->name('produksis.export');

    Route::resource('projeks', ProjekController::class);
    Route::put('projeks/{projek}/selesai', [ProjekController::class, 'updateStatus'])->name('projeks.updateStatus');
    Route::get('projeks-export/{projek_id}', [ProjekController::class, 'export'])->name('projeks.export');

    Route::resource('bahan-rusaks', BahanRusakController::class);
    Route::resource('bahan-setengahjadis', BahanSetengahjadiController::class);
    Route::resource('bahan-jadis', BahanJadiController::class);
    Route::resource('produk-produksis', ProdukProduksiController::class);
    Route::resource('bahan-returs', BahanReturController::class);

    Route::resource('stock-opname', StockOpnameController::class);
    Route::put('/stock-opname/updateApprovalFinance/{id}', [StockOpnameController::class, 'updateApprovalFinance'])->name('stock-opname.updateApprovalFinance');
    Route::put('/stock-opname/updateApprovalDirektur/{id}', [StockOpnameController::class, 'updateApprovalDirektur'])->name('stock-opname.updateApprovalDirektur');
    Route::put('/stock-opname/selesaiStockOpname/{id}', [StockOpnameController::class, 'selesaiStockOpname'])->name('stock-opname.selesaiStockOpname');
    Route::get('/stock-opnam/pdf/{id}', [StockOpnameController::class, 'downloadPdf'])->name('stock-opname.downloadPdf');

    Route::resource('projek-rnd', ProjekRndController::class);
    Route::put('projek-rnd/{projek}/selesai', [ProjekRndController::class, 'updateStatus'])->name('projek-rnd.updateStatus');
    Route::get('projek-rnd-export/{projek_rnd_id}', [ProjekRndController::class, 'export'])->name('projek-rnd.export');


    Route::resource('pengambilan-bahan', PengambilanBahanController::class);
    Route::put('pengambilan-bahan/{pengajuan}/selesai', [PengambilanBahanController::class, 'updateStatus'])->name('pengambilan-bahan.updateStatus');



    Route::fallback(function() {
        return view('pages/utility/404');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});


