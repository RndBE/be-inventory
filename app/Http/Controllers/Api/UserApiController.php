<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserApiController extends Controller
{
    /**
     * Ambil semua user dengan data relevan untuk WhatsApp AI Assistant.
     * GET /api/whatsapp/users
     */
    public function index(Request $request)
    {
        $users = User::with(['dataJobPosition', 'dataOrganization'])
            ->whereNotNull('telephone')
            ->where('telephone', '!=', '')
            ->get()
            ->map(function ($user) {
                return [
                    'id'           => $user->id,
                    'name'         => $user->name,
                    'telephone'    => $user->telephone,
                    'email'        => $user->email,
                    'job_position' => $user->dataJobPosition?->nama ?? null,
                    'organization' => $user->dataOrganization?->nama ?? null,
                    'job_level'    => $user->job_level,
                    'status'       => $user->status,
                ];
            });

        return response()->json([
            'success' => true,
            'total'   => $users->count(),
            'data'    => $users,
        ]);
    }

    /**
     * Cari user berdasarkan nomor telepon.
     * GET /api/whatsapp/users/by-phone?phone=628xxx
     */
    public function findByPhone(Request $request)
    {
        $phone = $request->query('phone');

        if (!$phone) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter phone wajib diisi.',
            ], 422);
        }

        $user = User::with(['dataJobPosition', 'dataOrganization'])
            ->where('telephone', $phone)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User dengan nomor telepon tersebut tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => $user->id,
                'name'         => $user->name,
                'telephone'    => $user->telephone,
                'email'        => $user->email,
                'job_position' => $user->dataJobPosition?->nama ?? null,
                'organization' => $user->dataOrganization?->nama ?? null,
                'job_level'    => $user->job_level,
                'status'       => $user->status,
            ],
        ]);
    }
}
