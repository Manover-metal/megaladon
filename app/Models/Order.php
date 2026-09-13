<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, CrudTrait;

    protected $fillable = ['title', 'description', 'budget', 'execution_days', 'category_id', 'status', 'user_id', 'city_id', 'executor_id'];

    const STATUS_MODERATE = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_HAS_EXECUTOR = 3;
    const STATUS_COMPLETED = 4;
    const STATUS_ARCHIVE = 5;

    // Статусы, которые показывают списки «моих» заказов в приложении
    // (OrderIndexRequestParams.statuses): без модерации и архива. По ним же
    // считаются бейджи — см. OrderViewRepo.
    const LISTED_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_HAS_EXECUTOR,
        self::STATUS_COMPLETED,
    ];

    public function media()
    {
        return $this->morphMany(MediaFiles::class, 'mediable');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'order_id');
    }

    public function offers()
    {
        return $this->hasMany(OrderOffer::class, 'order_id');
    }

    // Отметки «просмотрено» по пользователям. В выдачу подгружается только
    // строка смотрящего — см. OrderRepo::index() и параметр viewer_id.
    public function views()
    {
        return $this->hasMany(OrderView::class, 'order_id');
    }

    // withTrashed: заказ остаётся видимым исполнителю, даже если заказчик
    // удалил аккаунт — он отдаётся как «Удалённый аккаунт» (см. UserPresenter).
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function executor()
    {
        return $this->belongsTo(Executor::class, 'executor_id');
    }

    public function category()
    {
        return $this->belongsTo(OrderCategory::class, 'category_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function getStatusName() : string
    {
        switch ($this->status) {
            case self::STATUS_MODERATE:
                return 'На проверке';
            case self::STATUS_ACTIVE:
                return 'Активный';
            case self::STATUS_HAS_EXECUTOR:
                return 'В работе';
            case self::STATUS_COMPLETED:
                return 'Выполнен';
            case self::STATUS_ARCHIVE:
                return 'В архиве';
        }
        return '';
    }

    public function countOffers()
    {
        return $this->offers()->count();
    }
}
