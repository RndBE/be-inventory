<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Token untuk CRM, sengaja dipisah dari `inventory_api_token`.
 *
 * Endpoint CRM membuka harga modal — data yang paling sensitif secara komersial
 * di sistem ini. Kalau CRM memakai key yang sama dengan HRIS dan WhatsApp,
 * satu kebocoran dari sisi mana pun langsung membuka semuanya, dan key-nya tidak
 * bisa dicabut sendiri-sendiri. Key terpisah membuat pencabutan jadi murah.
 *
 * Dibaca lewat config(), bukan env() langsung: begitu `php artisan config:cache`
 * dijalankan, .env tidak lagi dimuat dan env() akan mengembalikan null.
 */
class CrmApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $expected = (string) config('services.crm.key');

        if ($expected === '') {
            return response()->json([
                'success' => false,
                'message' => 'Integrasi CRM belum dikonfigurasi di inventory.',
            ], 503);
        }

        $given = (string) $request->header('X-API-KEY');

        if (!hash_equals($expected, $given)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return $next($request);
    }
}
