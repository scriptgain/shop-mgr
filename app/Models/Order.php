<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use \App\Models\Concerns\Auditable;

    public const STATUSES = ['open', 'cancelled', 'archived'];

    public const FINANCIAL_STATUSES = ['pending', 'paid', 'partially_refunded', 'refunded', 'failed', 'voided'];

    public const FULFILLMENT_STATUSES = ['unfulfilled', 'partially_fulfilled', 'fulfilled', 'returned'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->latest();
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(Fulfillment::class)->latest();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Allocate the next sequential order number inside a transaction, so two
     * simultaneous checkouts can never mint the same number.
     */
    public static function nextNumber(): string
    {
        $prefix = (string) config('shop.order_prefix', 'SM-');
        $start = (int) config('shop.order_start_number', 1000);

        return DB::transaction(function () use ($prefix, $start) {
            $last = static::lockForUpdate()
                ->where('number', 'like', $prefix.'%')
                ->orderByRaw('LENGTH(number) DESC, number DESC')
                ->value('number');

            $next = $last
                ? ((int) preg_replace('/\D/', '', substr($last, strlen($prefix))) + 1)
                : $start;

            return $prefix.$next;
        });
    }

    /** Append a timeline entry. Every status change should write one. */
    public function recordEvent(string $type, string $message, array $meta = [], ?int $userId = null): OrderEvent
    {
        return $this->events()->create([
            'type' => $type,
            'message' => $message,
            'meta' => $meta ?: null,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }

    /* ---- Derived state (keeps conditionals out of Blade) -------------- */

    public function getIsPaidAttribute(): bool
    {
        return in_array($this->financial_status, ['paid', 'partially_refunded'], true);
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'cancelled';
    }

    public function getIsFullyFulfilledAttribute(): bool
    {
        return $this->fulfillment_status === 'fulfilled';
    }

    /** Whether staff can still act on this order. */
    public function getIsActionableAttribute(): bool
    {
        return $this->status === 'open';
    }

    public function getItemCountAttribute(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function getNetTotalCentsAttribute(): int
    {
        return $this->total_cents - $this->refunded_cents;
    }

    public function getCustomerNameAttribute(): string
    {
        $shipping = $this->shipping_address ?? [];
        $name = trim(($shipping['first_name'] ?? '').' '.($shipping['last_name'] ?? ''));

        return $name ?: ($this->customer?->name ?? $this->email);
    }

    /* ---- Formatted money, so views never call a helper ---------------- */

    public function getSubtotalFormattedAttribute(): string
    {
        return Money::format($this->subtotal_cents);
    }

    public function getDiscountFormattedAttribute(): string
    {
        return Money::format($this->discount_cents);
    }

    public function getShippingFormattedAttribute(): string
    {
        return Money::format($this->shipping_cents);
    }

    public function getTaxFormattedAttribute(): string
    {
        return Money::format($this->tax_cents);
    }

    public function getTotalFormattedAttribute(): string
    {
        return Money::format($this->total_cents);
    }

    public function getNetTotalFormattedAttribute(): string
    {
        return Money::format($this->net_total_cents);
    }

    public function getRefundedFormattedAttribute(): string
    {
        return Money::format($this->refunded_cents);
    }

    /* ---- Badge colours, resolved on the model (not in the view) ------- */

    public function getFinancialBadgeAttribute(): string
    {
        return [
            'paid' => 'success',
            'partially_refunded' => 'warn',
            'refunded' => 'neutral',
            'failed' => 'danger',
            'voided' => 'neutral',
        ][$this->financial_status] ?? 'warn';
    }

    public function getFulfillmentBadgeAttribute(): string
    {
        return [
            'fulfilled' => 'success',
            'partially_fulfilled' => 'warn',
            'returned' => 'danger',
        ][$this->fulfillment_status] ?? 'neutral';
    }

    public function getStatusBadgeAttribute(): string
    {
        return ['cancelled' => 'danger', 'archived' => 'neutral'][$this->status] ?? 'info';
    }

    /* ---- Scopes ------------------------------------------------------- */

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('number', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhereHas('items', fn (Builder $i) => $i->where('sku', 'like', "%{$term}%"));
        });
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->whereIn('financial_status', ['paid', 'partially_refunded']);
    }

    /**
     * Recompute fulfillment_status from the line items. Called after every
     * fulfillment so the header badge can never disagree with the items table.
     */
    public function syncFulfillmentStatus(): void
    {
        $items = $this->items()->where('requires_shipping', true)->get();

        if ($items->isEmpty()) {
            $status = 'fulfilled';
        } elseif ($items->every(fn (OrderItem $i) => $i->fulfilled_qty >= $i->quantity)) {
            $status = 'fulfilled';
        } elseif ($items->contains(fn (OrderItem $i) => $i->fulfilled_qty > 0)) {
            $status = 'partially_fulfilled';
        } else {
            $status = 'unfulfilled';
        }

        if ($this->fulfillment_status !== $status) {
            $this->forceFill(['fulfillment_status' => $status])->save();
        }
    }
}
