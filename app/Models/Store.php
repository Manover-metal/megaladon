<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory, CrudTrait;

    protected $fillable = [
        'user_id',
        'type_id',
        'name',
        'bin',
        'city_id',
        'lat',
        'lon',
        'full_address',
        'rating',
    ];

    public function contacts()
    {
        return $this->hasMany(StoreContacts::class, 'store_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function type()
    {
        return $this->belongsTo(CompanyType::class, 'type_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function media()
    {
        return $this->morphMany(MediaFiles::class, 'mediable');
    }

    public function ratings()
    {
        return $this->morphMany(Rating::class, 'ratingable');
    }

    public function invoices()
    {
        return $this->morphMany(Invoice::class, 'invoiceable');
    }

    public function activeInvoice()
    {
        return $this->invoices()
            ->where('status', Invoice::STATUS_PAID)
            ->whereDate('expired_at', '>', Carbon::now())
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getContacts()
    {
        $contacts = $this->contacts()->get();
        $result = '';

        foreach ($contacts as $contact) {
            if ($contact->type == 'site') {
                $result .= 'Сайт: ' . $contact->value . '<br>';
            }
            elseif ($contact->type == 'email') {
                $result .= 'Email: ' . $contact->value . '<br>';
            }
            elseif (in_array($contact->type, ['phone', 'home_phone'])) {
                $result .= $contact->contact_name . ' - ' . $contact->value . '<br>';
            }
        }

        return $result;
    }
}
