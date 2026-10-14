<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Cashier\Subscription as CashierSubscription;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $subscription_id
 * @property string $stripe_event_id                       Unique; idempotency key
 * @property string $type                                  Stripe event type
 * @property array $payload                                Full Stripe event JSON
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read User|null $user
 * @property-read CashierSubscription|null $subscription
 */
class SubscriptionEvent extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionEventFactory> */
    use HasFactory;

    protected $table = 'subscription_events';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'subscription_id',
        'stripe_event_id',
        'type',
        'payload',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * The user this event references (nullable; some events have no user).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The Cashier subscription this event references (nullable; late events
     * can survive a pruned subscription row).
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CashierSubscription::class, 'subscription_id');
    }
}
