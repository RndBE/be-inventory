<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    use HasFactory;
    protected $table = 'stock_opname';
    protected $guarded = [];

    public function stockOpnameDetails()
    {
        return $this->hasMany(StockOpnameDetails::class, 'stock_opname_id');
    }

    public function approvalKendalas()
    {
        return $this->hasMany(ApprovalKendala::class, 'module_id')
            ->where('module', 'stock_opname');
    }

    public function kendalaApproval(string $role): ?string
    {
        $notes = $this->relationLoaded('approvalKendalas')
            ? $this->approvalKendalas
            : $this->approvalKendalas()->get();

        return optional($notes->firstWhere('approval_role', $role))->kendala;
    }

    public function pengajuUser()
    {
        return $this->belongsTo(User::class, 'pengaju');
    }

}
