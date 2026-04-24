<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordResetAudit extends Model
{
    protected $fillable = [
        'user_id',
        'reset_by_admin_id',
        'reason',
        'ip_address',
    ];

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resetByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reset_by_admin_id');
    }
}
