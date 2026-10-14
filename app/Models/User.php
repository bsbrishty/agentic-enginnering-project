<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $stripe_id          Cashier: Stripe customer id
 * @property string|null $pm_type            Cashier: last payment method type
 * @property string|null $pm_last_four       Cashier: last 4 of card
 * @property \Illuminate\Support\Carbon|null $trial_ends_at  Cashier: trial end
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Cashier\Subscription> $subscriptions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Space> $spaces
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SubscriptionEvent> $subscriptionEvents
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Billable;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        // Cashier columns
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
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
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Spaces owned by this user.
     */
    public function spaces(): HasMany
    {
        return $this->hasMany(Space::class);
    }

    /**
     * Subscription events referencing this user.
     */
    public function subscriptionEvents(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class);
    }
}
