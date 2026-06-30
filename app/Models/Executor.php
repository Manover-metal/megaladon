<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Executor extends Model
{
    use HasFactory, CrudTrait;

    protected $fillable = [
        'user_id', 
        'city_id',
        'name',
        'description',
        'bin',
        'lat',
        'lon',
        'full_address',
        'rating',
    ];

    public function services()
    {
        return $this->belongsToMany(ServiceType::class, 'executor_service_types');
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

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
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
