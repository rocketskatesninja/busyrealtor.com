<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Services\TenantMailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'tenant_id',
        'email_verified_at',
        'unsubscribed_at',
        'is_super_admin',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_super_admin'         => 'boolean',
        'email_verified_at'      => 'datetime',
        'unsubscribed_at'        => 'datetime',
        'password'               => 'hashed',
        'failed_login_attempts'  => 'integer',
        'locked_until'           => 'datetime',
    ];


    /**
     * Backward-compatible full name accessor.
     */
    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Send verification email using tenant SMTP + branded template.
     */
    public function sendEmailVerificationNotification(): void
    {
        if ($this->tenant_id) {
            $url = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(60),
                ['id' => $this->getKey(), 'hash' => sha1($this->getEmailForVerification())]
            );

            $body = "Hi {$this->first_name},\n\nPlease verify your email address by clicking the link below:\n\n{$url}\n\nThis link will expire in 60 minutes.\n\nIf you did not request this, no action is needed.";

            TenantMailer::send(
                $this->tenant_id,
                $this->email,
                'Verify Your Email Address',
                $body,
                'tenant',
                $this->name
            );
            return;
        }

        parent::sendEmailVerificationNotification();
    }

    /**
     * End this user's sessions everywhere except the one making the request.
     *
     * Changing a password is how you evict someone who already holds a session,
     * so it has to actually end theirs. It lives here rather than in the two
     * controllers that change passwords, because the day those two disagree is a
     * security hole rather than an inconsistency.
     *
     * Only the database driver keeps rows addressable by user; under any other
     * driver this is a no-op and the caller is no worse off than before.
     */
    public function endOtherSessions(): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $this->getKey())
            ->where('id', '!=', session()->getId())
            ->delete();
    }
}
