<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalKendala extends Model
{
    use HasFactory;

    protected $table = 'approval_kendalas';
    protected $guarded = [];

    public static function saveFor(string $module, int $moduleId, string $role, ?string $status, ?string $kendala, ?int $userId = null): ?self
    {
        $kendala = trim((string) $kendala);

        if ($kendala === '') {
            static::where('module', $module)
                ->where('module_id', $moduleId)
                ->where('approval_role', $role)
                ->delete();

            return null;
        }

        return static::updateOrCreate(
            [
                'module' => $module,
                'module_id' => $moduleId,
                'approval_role' => $role,
            ],
            [
                'status' => $status,
                'kendala' => $kendala,
                'user_id' => $userId,
            ]
        );
    }

    public static function getKendala(string $module, int $moduleId, string $role): ?string
    {
        return static::where('module', $module)
            ->where('module_id', $moduleId)
            ->where('approval_role', $role)
            ->value('kendala');
    }
}
