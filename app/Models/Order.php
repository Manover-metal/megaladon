<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, CrudTrait;

    protected $fillable = ['title', 'description', 'price_recommended', 'price_max', 'execution_days', 'category_id', 'status', 'user_id', 'city_id', 'executor_id'];

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

    // Заказы вкладки «как исполнитель»: где пользователь назначен, и где он
    // откликнулся, пока исполнитель не выбран. Как только заказчик выбрал
    // другого, заказ из набора уходит.
    //
    // Правило живёт здесь, а не в репозиториях, потому что им пользуются и
    // список (OrderRepo::index), и счётчик бейджа
    // (OrderViewRepo::countRespondedWithUpdates). Две копии условия
    // разъедутся, и число на вкладке перестанет сходиться с её содержимым.
    //
    // «Исполнитель не выбран» — это null ИЛИ 0: колонка nullable, но
    // OrderService::create пишет 0, и в базе встречаются оба значения.
    public function scopeVisibleToExecutor($query, int $userId, int $executorId)
    {
        return $query->where(function ($q) use ($userId, $executorId) {
            $q->where('orders.executor_id', $executorId)
                ->orWhere(function ($q2) use ($userId) {
                    $q2->where(function ($q3) {
                            $q3->whereNull('orders.executor_id')
                                ->orWhere('orders.executor_id', 0);
                        })
                        ->whereExists(function ($sub) use ($userId) {
                            $sub->selectRaw(1)
                                ->from('order_offers')
                                ->whereColumn('order_offers.order_id', 'orders.id')
                                ->where('order_offers.user_id', $userId);
                        });
                });
        });
    }

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
