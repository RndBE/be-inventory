<?php

namespace App\Jobs;

use App\Helpers\LogHelper;
use App\Models\BahanKeluar;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function handle()
    {
        $data = BahanKeluar::with('dataUser')->find($this->id);

        if (!$data || !$data->dataUser) {
            return;
        }

        $pengajuPhone = $data->dataUser->telephone;
        $name = $data->dataUser->name;
        $transactionCode = $data->kode_transaksi;

        if ($pengajuPhone) {
            $message = "Halo {$name},\n\n";
            $message .= "Barang siap diambil di gudang. Pengajuan pengambilan barang dengan Kode Transaksi {$transactionCode} sudah siap.\n\n";
            $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";

            try {
                $responsePengaju = Http::withHeaders([
                    'x-api-key' => env('WHATSAPP_API_KEY'),
                    'Content-Type' => 'application/json',
                ])->post('http://31.58.158.182:3000/client/sendMessage/beacon', [
                    'chatId' => "{$pengajuPhone}@c.us",
                    'contentType' => 'string',
                    'content' => $message,
                ]);

                if ($responsePengaju->successful()) {
                    LogHelper::success("Pesan berhasil dikirim ke {$name} ({$pengajuPhone})");
                } else {
                    LogHelper::error("Gagal mengirim pesan ke {$name} ({$pengajuPhone})");
                }
            } catch (\Exception $e) {
                LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
            }
        }
    }
}
