<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'phone',
        'department',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** A referral partner's tracking code, if this user is one. */
    public function referral(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Referral::class);
    }

    /** The Hospi Sales tenant this account belongs to (null for legacy admins). */
    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isReferralPartner(): bool
    {
        return $this->hasRole('Referral')
            && ! $this->hasAnyRole(['Super Admin', 'Admin', 'Manager']);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->email === 'victorynonso9@gmail.com'
            || $this->hasAnyRole(['Super Admin', 'super_admin', 'admin', 'Manager', 'Front Desk', 'frontdesk', 'Space Host', 'Scanner']);
        // 'Referral' is deliberately absent: partners sign in at /referral
        // against their own guard and must never reach staff data.
    }
}


