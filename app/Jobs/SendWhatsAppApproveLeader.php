<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Helpers\LogHelper;

class SendWhatsAppApproveLeader implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phone;
    protected $message;

    /**
     * Create a new job instance.
     */
    public function __construct($phone, $message)
    {
        $this->phone = $phone;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if (empty($this->phone)) {
            LogHelper::error('SendWhatsAppApproveLeader: Phone number is empty.');
            return;
        }

        try {
            // Bersihkan nomor telepon dari karakter non-numerik (seperti spasi, +, strip)
            $cleanPhone = preg_replace('/[^0-9]/', '', $this->phone);
            
            // Format wajib diawali 62 untuk Indonesia (jika awalnya 0, ubah jadi 62)
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
                LogHelper::success("WhatsApp message (Approve Leader) sent successfully to: {$cleanPhone}");
            } else {
                LogHelper::error("Failed to send WhatsApp message (Approve Leader) to {$cleanPhone}. Response: " . $response->body());
            }
        } catch (\Exception $e) {
            LogHelper::error("Exception in SendWhatsAppApproveLeader for {$this->phone}: {$e->getMessage()}");
        }
    }
}
