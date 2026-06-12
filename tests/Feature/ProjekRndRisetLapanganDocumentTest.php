<?php

namespace Tests\Feature;

use App\Http\Controllers\ProjekRndController;
use App\Models\ProjekRnd;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjekRndRisetLapanganDocumentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('projek_rnd', function (Blueprint $table) {
            $table->id();
            $table->string('kode_projek_rnd')->unique();
            $table->dateTime('mulai_projek_rnd');
            $table->dateTime('selesai_projek_rnd')->nullable();
            $table->string('pengaju')->nullable();
            $table->string('keterangan')->nullable();
            $table->text('keterangan_status')->nullable();
            $table->string('nama_projek_rnd');
            $table->string('status');
            $table->string('serial_number')->nullable();
            $table->string('file_laporan')->nullable();
            $table->boolean('is_riset_lapangan')->default(false);
            $table->string('file_proposal_riset')->nullable();
            $table->string('file_surat_tugas_riset')->nullable();
            $table->timestamps();
        });

        Schema::create('log_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('method')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('url');
            $table->string('platform')->nullable();
            $table->string('browser')->nullable();
            $table->string('status')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function test_upload_proposal_riset_lapangan_updates_project_document_path(): void
    {
        Storage::fake('public');
        $projekRnd = $this->createRisetLapangan();

        $request = Request::create(
            '/projek-rnd/' . $projekRnd->id . '/upload-proposal-riset',
            'POST',
            [],
            [],
            ['file_proposal_riset' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf')]
        );

        app(ProjekRndController::class)->uploadProposalRiset($request, $projekRnd->id);

        $projekRnd->refresh();

        $this->assertNotNull($projekRnd->file_proposal_riset);
        Storage::disk('public')->assertExists($projekRnd->file_proposal_riset);
    }

    public function test_upload_surat_tugas_riset_lapangan_updates_project_document_path(): void
    {
        Storage::fake('public');
        $projekRnd = $this->createRisetLapangan();

        $request = Request::create(
            '/projek-rnd/' . $projekRnd->id . '/upload-surat-tugas-riset',
            'POST',
            [],
            [],
            ['file_surat_tugas_riset' => UploadedFile::fake()->create('surat-tugas.pdf', 100, 'application/pdf')]
        );

        app(ProjekRndController::class)->uploadSuratTugasRiset($request, $projekRnd->id);

        $projekRnd->refresh();

        $this->assertNotNull($projekRnd->file_surat_tugas_riset);
        Storage::disk('public')->assertExists($projekRnd->file_surat_tugas_riset);
    }

    private function createRisetLapangan(): ProjekRnd
    {
        return ProjekRnd::create([
            'kode_projek_rnd' => 'PJRnD - 00001',
            'mulai_projek_rnd' => now(),
            'nama_projek_rnd' => 'Riset Lapangan Produk Baru',
            'status' => 'Dalam Proses',
            'is_riset_lapangan' => true,
        ]);
    }
}
