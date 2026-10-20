<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
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

    /**
     * Roles admitted to the staff panel.
     *
     * 'Host' is accepted as an alias of 'Space Host'. The canonical name is
     * 'Space Host' (see RoleSeeder — the planned Hospi Sales app has its own
     * unrelated restaurant Host role), but RoleResource lets staff create any
     * role they like, and a user given a role named plainly 'Host' was
     * authenticating correctly and then being refused the panel. Filament
     * reports that refusal with the SAME error as a wrong password
     * (Filament\Pages\Auth\Login::authenticate() calls
     * throwFailureValidationException() for both), so it looked like the
     * password was wrong when it never was.
     */
    public const PANEL_ROLES = [
        'Super Admin', 'super_admin',
        'admin',
        'Manager',
        'Front Desk', 'frontdesk',
        'Space Host', 'Host',
        'Scanner',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->email === 'victorynonso9@gmail.com') {
            return true;
        }

        if ($this->hasAnyRole(self::PANEL_ROLES)) {
            return true;
        }

        // The password was already accepted by this point, so without this the
        // only trace of the refusal is a generic "credentials do not match"
        // on the form. Deliberately a log line and not a friendlier on-screen
        // message: Filament uses one message for both failures to stop account
        // enumeration, and that is worth keeping.
        Log::warning('Panel access refused after successful authentication', [
            'user_id' => $this->id,
            'email' => $this->email,
            'roles' => $this->getRoleNames()->all(),
            'panel' => $panel->getId(),
        ]);

        return false;
        // 'Referral' is deliberately absent: partners sign in at /referral
        // against their own guard and must never reach staff data.
    }

    /**
     * Emails are normalised on write so a trailing space or a stray capital
     * cannot create an account nobody can log into. Login lookups are exact
     * string comparisons on some drivers, and the address typed into the
     * Filament form is not trimmed by anything else.
     */
    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value === null ? null : strtolower(trim($value));
    }
}


