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
        try {
            $response = Http::withHeaders([
                'x-api-key' => env('WHATSAPP_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                'chatId' => "{$this->phone}@c.us",
                'contentType' => 'string',
                'content' => $this->message,
            ]);

            if ($response->successful()) {
                LogHelper::success("WhatsApp message sent to: {$this->phone}");
            } else {
                LogHelper::error("Failed to send WhatsApp message to: {$this->phone}");
            }
        } catch (\Exception $e) {
            LogHelper::error("Error sending WhatsApp message: {$e->getMessage()}");
        }
    }
}
