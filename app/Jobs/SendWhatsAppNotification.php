<?php

namespace App\Jobs;

use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $targetPhone;
    protected $message;
    protected $recipientName;

    /**
     * Create a new job instance.
     */
    public function __construct($targetPhone, $message, $recipientName)
    {
        $this->targetPhone = $targetPhone;
        $this->message = $message;
        $this->recipientName = $recipientName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->targetPhone) {
            LogHelper::error('No valid phone number found for WhatsApp notification.');
            return;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => env('WHATSAPP_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('http://31.58.158.182:3000/client/sendMessage/beacon', [
                'chatId' => "{$this->targetPhone}@c.us",
                'contentType' => 'string',
                'content' => $this->message,
            ]);

            if ($response->successful()) {
                LogHelper::success("Pesan berhasil dikirim ke ({$this->targetPhone}) {$this->recipientName}");
            } else {
                LogHelper::error("Gagal mengirim pesan ke ({$this->targetPhone}) {$this->recipientName}");
            }
        } catch (\Exception $e) {
            LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
        }
    }
}
