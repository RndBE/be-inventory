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
        if (empty($this->targetPhone)) {
            LogHelper::error('No valid phone number found for WhatsApp notification.');
            return;
        }

        try {
            $cleanPhone = preg_replace('/[^0-9]/', '', $this->targetPhone);
            if (substr($cleanPhone, 0, 1) == '0') {
                $cleanPhone = '62' . substr($cleanPhone, 1);
            }

            $response = Http::withHeaders([
                'x-api-key' => env('WHATSAPP_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('http://72.60.78.159:3000/client/sendMessage/beacon', [
                'chatId' => "{$cleanPhone}@c.us",
                'contentType' => 'string',
                'content' => $this->message,
            ]);

            if ($response->successful()) {
                LogHelper::success("Pesan berhasil dikirim ke {$cleanPhone} ({$this->recipientName})");
            } else {
                LogHelper::error("Gagal mengirim pesan ke {$cleanPhone} ({$this->recipientName}). Response: " . $response->body());
            }
        } catch (\Exception $e) {
            LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
        }
    }
}
