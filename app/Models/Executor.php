<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Executor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'bin',
        'full_address',
        'rating',
    ];

    protected static function booted(): void
    {
        // Связку чистим сами: внешних ключей с cascade в проекте нет.
        static::deleting(fn (Executor $e) => $e->services()->detach());
    }

    public function services()
    {
        return $this->belongsToMany(OrderCategory::class, 'executor_service_types', 'executor_id', 'order_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ratings()
    {
        return $this->morphMany(Rating::class, 'ratingable');
    }

    public function invoices()
    {
        return $this->morphMany(Invoice::class, 'invoiceable');
    }

    /** Можно пользоваться платными функциями: подписки выключены или оплачено. */
    public function hasAccess(): bool
    {
        return !Subscription::enabled() || !is_null($this->activeInvoice());
    }

    /** subscription_expired_at для приложения (unix-время), null — доступа нет. */
    public function accessExpiresAt(): ?int
    {
        if (!Subscription::enabled()) {
            return strtotime(Subscription::UNLIMITED_UNTIL);
        }
        $invoice = $this->activeInvoice();
        return is_null($invoice) ? null : strtotime($invoice->expired_at);
    }

    public function activeInvoice()
    {
        return $this->invoices()
            ->where('status', Invoice::STATUS_PAID)
            ->whereDate('expired_at', '>', Carbon::now())
            ->orderBy('id', 'desc')
            ->first();
    }
}
