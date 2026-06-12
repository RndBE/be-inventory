<?php

namespace Tests\Feature;

use App\Http\Controllers\ProjekRndController;
use App\Models\User;
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

        Schema::create('job_position', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('job_position_id')->nullable();
            $table->integer('job_level')->nullable();
            $table->string('telephone')->nullable();
            $table->unsignedBigInteger('atasan_level1_id')->nullable();
            $table->unsignedBigInteger('atasan_level2_id')->nullable();
            $table->unsignedBigInteger('atasan_level3_id')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
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

    public function test_edit_internal_project_to_riset_lapangan_stores_uploaded_documents(): void
    {
        Storage::fake('public');
        $user = User::create([
            'name' => 'RND User',
            'email' => 'rnd@example.com',
            'password' => 'secret',
            'job_level' => 3,
        ]);
        $this->actingAs($user);

        $projekRnd = ProjekRnd::create([
            'kode_projek_rnd' => 'PJRnD - 00002',
            'mulai_projek_rnd' => now(),
            'nama_projek_rnd' => 'Riset Internal Produk',
            'keterangan' => 'Awalnya internal',
            'status' => 'Dalam Proses',
            'is_riset_lapangan' => false,
        ]);

        $request = Request::create(
            '/projek-rnd/' . $projekRnd->id,
            'PUT',
            [
                'nama_projek_rnd' => 'Riset Internal Produk',
                'keterangan' => 'Diubah menjadi riset lapangan',
                'serial_number' => null,
                'is_riset_lapangan' => '1',
            ],
            [],
            [
                'file_proposal_riset' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
                'file_surat_tugas_riset' => UploadedFile::fake()->create('surat-tugas.pdf', 100, 'application/pdf'),
            ]
        );

        app(ProjekRndController::class)->update($request, $projekRnd->id);

        $projekRnd->refresh();

        $this->assertTrue((bool) $projekRnd->is_riset_lapangan);
        $this->assertNotNull($projekRnd->file_proposal_riset);
        $this->assertNotNull($projekRnd->file_surat_tugas_riset);
        Storage::disk('public')->assertExists($projekRnd->file_proposal_riset);
        Storage::disk('public')->assertExists($projekRnd->file_surat_tugas_riset);
    }

    public function test_edit_riset_lapangan_with_existing_documents_does_not_require_reupload(): void
    {
        Storage::fake('public');
        $user = User::create([
            'name' => 'RND User',
            'email' => 'rnd-existing@example.com',
            'password' => 'secret',
            'job_level' => 3,
        ]);
        $this->actingAs($user);

        Storage::disk('public')->put('proposal-riset/existing-proposal.pdf', 'proposal');
        Storage::disk('public')->put('surat-tugas-riset/existing-surat.pdf', 'surat tugas');

        $projekRnd = ProjekRnd::create([
            'kode_projek_rnd' => 'PJRnD - 00003',
            'mulai_projek_rnd' => now(),
            'nama_projek_rnd' => 'Riset Lapangan Produk',
            'keterangan' => 'Sudah punya dokumen',
            'status' => 'Dalam Proses',
            'is_riset_lapangan' => true,
            'file_proposal_riset' => 'proposal-riset/existing-proposal.pdf',
            'file_surat_tugas_riset' => 'surat-tugas-riset/existing-surat.pdf',
        ]);

        $request = Request::create('/projek-rnd/' . $projekRnd->id, 'PUT', [
            'nama_projek_rnd' => 'Riset Lapangan Produk Revisi',
            'keterangan' => 'Dokumen lama tetap dipakai',
            'serial_number' => null,
            'is_riset_lapangan' => '1',
        ]);

        app(ProjekRndController::class)->update($request, $projekRnd->id);

        $projekRnd->refresh();

        $this->assertTrue((bool) $projekRnd->is_riset_lapangan);
        $this->assertSame('proposal-riset/existing-proposal.pdf', $projekRnd->file_proposal_riset);
        $this->assertSame('surat-tugas-riset/existing-surat.pdf', $projekRnd->file_surat_tugas_riset);
        Storage::disk('public')->assertExists($projekRnd->file_proposal_riset);
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
