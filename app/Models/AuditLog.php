<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use App\Modules\Tenant\Traits\BelongsToTenant;

class AuditLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'action',
        'payload',
        'ip_address',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // ─── Static Helper ───────────────────────────────────────────────────────

    /**
     * Record an audit log entry.
     *
     * @param  string     $action   e.g. 'tenant.activated', 'impersonate.start'
     * @param  array      $payload  contextual data
     * @param  string|null $tenantId
     */
    public static function record(string $action, array $payload = [], ?string $tenantId = null): self
    {
        return static::create([
            'user_id'    => Auth::id(),
            'tenant_id'  => $tenantId,
            'action'     => $action,
            'payload'    => $payload,
            'ip_address' => Request::ip(),
        ]);
    }
}
