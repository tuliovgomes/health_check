<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, \Laravel\Cashier\Billable; 

    /**
     * User has many Links (for plans quota enforcement)
     */
    public function links()
    {
        return $this->hasMany(\App\Models\Link::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'plan',
        'links_quota',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
        'stripe_id',
        'pm_type',
        'pm_last_four',
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
            'two_factor_confirmed_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'links_quota' => 'integer',
            'plan' => 'string',
        ];
    }

    /**
     * Check if user is on the given plan key.
     */
    public function isOnPlan(string $plan): bool
    {
        return $this->plan === $plan;
    }

    /**
     * Returns remaining quota (null = unlimited).
     */
    public function linksQuotaRemaining(): ?int
    {
        if (is_null($this->links_quota)) {
            return null;
        }

        $used = $this->links()->count();

        return max(0, $this->links_quota - $used);
    }

    /**
     * Assign a plan to the user and set the links_quota accordingly.
     */
    public function assignPlan(string $planKey): self
    {
        $plan = config('plans.plans.' . $planKey);

        $this->plan = $planKey;
        $this->links_quota = $plan['links_quota'] ?? null;
        $this->save();

        return $this;
    }
}
