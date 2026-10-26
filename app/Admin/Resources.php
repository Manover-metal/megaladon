<?php

namespace App\Admin;

use App\Models\AdCategory;
use App\Models\Advert;
use App\Models\City;
use App\Models\Executor;
use App\Models\Invoice;
use App\Models\MediaFiles;
use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderOffer;
use App\Models\Rating;
use App\Models\Report;
use App\Models\ServiceType;
use App\Models\Setting;
use App\Models\Store;
use App\Models\StoreContacts;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\v1\PushService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Разделы админки: что и как читать/писать. Отображение (колонки, подписи,
 * формы) живёт в megaladon_admin/src/lib/resources/index.ts, здесь — данные.
 *
 *  model       Eloquent-модель раздела
 *  search      колонки для LIKE-поиска
 *  defaultSort [колонка, asc|desc]
 *  relations   имя => [Модель, колонка-подпись | fn(Model): string, where для вариантов формы]
 *              — подписи relation-колонок списка и варианты select-ов фильтров/формы
 *  filters     имя => 'eq' | 'boolean' | 'daterange' | fn(Builder, string $v)
 *  fields      имя => тип ('text', 'number', 'relation', 'select', 'boolean',
 *              'date', 'password', 'image', 'multiselect', 'readonly'); '!' — обязательное
 *  create/edit/delete  false — операция запрещена
 *  fieldOptions, listExtra, extra, formValues, beforeSave, afterSave, action — хуки
 */
class Resources
{
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /** Счётчики «требует внимания» для сайдбара. */
    public static function badges(): array
    {
        return [
            'advert' => Advert::where('status', Advert::STATUS_MODERATE)->count(),
            'order' => Order::where('status', Order::STATUS_MODERATE)->count(),
            'support-ticket' => SupportTicket::where('status', SupportTicket::STATUS_OPEN)->count(),
            'report' => Report::where('status', Report::STATUS_OPEN)->count(),
        ];
    }

    /** Дашборд: всего записей и новые по месяцам текущего года. */
    public static function stats(): array
    {
        $out = [];
        foreach (['users', 'orders', 'adverts', 'executors', 'stores'] as $t) {
            $byMonth = array_fill(0, 12, 0);
            $rows = DB::table($t)->selectRaw('month(created_at) m, count(*) c')
                ->whereYear('created_at', now()->year)->groupBy('m')->get();
            foreach ($rows as $r) {
                $byMonth[(int) $r->m - 1] = (int) $r->c;
            }
            $out[] = ['key' => $t, 'total' => DB::table($t)->count(), 'byMonth' => $byMonth];
        }

        return $out;
    }

    private static function all(): array
    {
        $user = [User::class, 'name'];
        $userPhone = [User::class, fn (User $u) => "$u->name ($u->phone)"];
        $city = [City::class, 'name'];
        $today = fn () => now()->toDateString();

        // Исполнители/магазины с действующей оплаченной подпиской (как scopeHasActiveSubscription).
        $subscribed = fn (Builder $q, string $v) => $q->{$v === 'yes' ? 'whereHas' : 'whereDoesntHave'}(
            'invoices',
            fn ($i) => $i->where('status', Invoice::STATUS_PAID)->whereDate('expired_at', '>', now())
        );

        // Подписи полиморфных владельцев (счета, отзывы) одним махом.
        $morph = function (Collection $models, string $typeCol, string $idCol): array {
            $out = [];
            foreach ([Executor::class => 'Исполнитель', Store::class => 'Магазин'] as $class => $prefix) {
                $ids = $models->where($typeCol, $class)->pluck($idCol)->unique()->all();
                if (!$ids) {
                    continue;
                }
                foreach ($class::whereIn('id', $ids)->get(['id', 'name']) as $m) {
                    $out["$class|$m->id"] = "$prefix — " . ($m->name ?: "#$m->id");
                }
            }

            return $out;
        };

        $push = fn () => app(PushService::class);

        return [
            'user' => [
                'model' => User::class,
                'delete' => false,
                'search' => ['name', 'phone', 'email'],
                'relations' => ['city_id' => $city],
                'filters' => [
                    'role' => 'eq',
                    'banned' => fn (Builder $q, string $v) => $v === 'yes' ? $q->whereNotNull('banned_at') : $q->whereNull('banned_at'),
                    'city_id' => 'eq',
                    'is_phone_confirmed' => 'boolean',
                    'kind' => fn (Builder $q, string $v) => $v === 'plain'
                        ? $q->whereNotIn('id', Executor::select('user_id'))->whereNotIn('id', Store::select('user_id'))
                        : $q->whereIn('id', ($v === 'executor' ? Executor::query() : Store::query())->select('user_id')),
                    'created_at' => 'daterange',
                ],
                // photo_url и password — мутаторы User: файл кладётся в storage, пароль хэшируется.
                'fields' => [
                    'photo_url' => 'image', 'name' => 'text!', 'phone' => 'text!', 'email' => 'text',
                    'is_phone_confirmed' => 'boolean', 'city_id' => 'relation!', 'role' => 'select!', 'password' => 'password!',
                ],
                'action' => function (string $key, User $u, string $input) {
                    $u->isBanned() ? $u->unban() : $u->ban($input ?: null);
                },
            ],

            'executor' => [
                'model' => Executor::class,
                'search' => ['name', 'bin', 'full_address'],
                'relations' => ['user_id' => $user, 'service' => [ServiceType::class, 'name'], 'services' => [ServiceType::class, 'name']],
                'filters' => [
                    'user_id' => 'eq',
                    'service' => fn (Builder $q, string $v) => $q->whereHas('services', fn ($s) => $s->where('service_types.id', (int) $v)),
                    'subscribed' => $subscribed,
                    'created_at' => 'daterange',
                ],
                'fields' => [
                    'user_id' => 'relation!', 'name' => 'text!', 'description' => 'text', 'services' => 'multiselect',
                    'bin' => 'text', 'full_address' => 'text!', 'lat' => 'number!', 'lon' => 'number!', 'rating' => 'number',
                ],
                'extra' => fn (Executor $e) => ['services' => $e->services()->pluck('name')->all()],
                'formValues' => fn (Executor $e) => ['services' => $e->services()->pluck('service_types.id')->map('strval')->all()],
                'afterSave' => fn (Executor $e, array $raw) => $e->services()->sync($raw['services'] ?? []),
            ],

            'store' => [
                'model' => Store::class,
                'search' => ['name', 'bin', 'full_address'],
                'relations' => ['user_id' => $user, 'city_id' => $city],
                'filters' => ['user_id' => 'eq', 'city_id' => 'eq', 'subscribed' => $subscribed, 'created_at' => 'daterange'],
                'fields' => [
                    'user_id' => 'relation!', 'city_id' => 'relation!', 'name' => 'text!', 'bin' => 'text!',
                    'full_address' => 'text!', 'lat' => 'number', 'lon' => 'number', 'rating' => 'number',
                ],
                'extra' => fn (Store $s) => ['contacts' => StoreContacts::where('store_id', $s->id)->get()->map(
                    fn ($c) => match ($c->type) { 'site' => "Сайт: $c->value", 'email' => "Email: $c->value", default => ($c->contact_name ?? '') . " - $c->value" }
                )->all()],
            ],

            'service-type' => [
                'model' => ServiceType::class,
                'search' => ['name'], 'defaultSort' => ['name', 'asc'],
                'filters' => ['created_at' => 'daterange'],
                'fields' => ['name' => 'text!'],
            ],

            'rating' => [
                'model' => Rating::class,
                'search' => ['comment'], 'defaultSort' => ['created_at', 'desc'],
                'relations' => ['user_id' => $user],
                'filters' => [
                    'user_id' => 'eq',
                    'ratingable_type' => 'eq',
                    'rate' => fn (Builder $q, string $v) => $q->where('rate', '>=', (int) $v)->where('rate', '<', (int) $v + 1),
                    'photos' => fn (Builder $q, string $v) => $q->{$v === 'yes' ? 'whereHas' : 'whereDoesntHave'}('media', fn ($m) => $m->whereNull('deleted_at')),
                    'created_at' => 'daterange',
                ],
                // ratingable_key ('App\Models\Store|5') разбирает мутатор Rating.
                'fields' => ['user_id' => 'relation!', 'ratingable_key' => 'select!', 'rate' => 'number!', 'comment' => 'text'],
                'fieldOptions' => [
                    'ratingable_key' => function () {
                        $out = [];
                        foreach (Rating::RATINGABLE_TYPES as $class => $prefix) {
                            foreach ($class::orderBy('name')->get(['id', 'name']) as $m) {
                                $out["$class|$m->id"] = "$prefix — " . ($m->name ?: "#$m->id");
                            }
                        }

                        return $out;
                    },
                ],
                'listExtra' => function (Collection $rows) use ($morph) {
                    $counts = MediaFiles::where('mediable_type', Rating::class)->whereNull('deleted_at')
                        ->whereIn('mediable_id', $rows->pluck('id'))->groupBy('mediable_id')
                        ->selectRaw('mediable_id, count(*) c')->pluck('c', 'mediable_id');

                    return ['morph' => $morph($rows, 'ratingable_type', 'ratingable_id'), 'mediaCount' => $counts->map('intval')->all()];
                },
                'extra' => fn (Rating $r) => ['media' => $r->media()->whereNull('deleted_at')->pluck('storage_link')->all()],
                'formValues' => fn (Rating $r) => ['ratingable_key' => "$r->ratingable_type|$r->ratingable_id"],
            ],

            'city' => [
                'model' => City::class,
                'search' => ['name'], 'defaultSort' => ['name', 'asc'],
                'filters' => ['created_at' => 'daterange'],
                'fields' => ['name' => 'text!'],
            ],

            'advert' => [
                'model' => Advert::class,
                'search' => ['title', 'additional_phone'],
                // На проверке — сверху.
                'defaultSort' => ['status', 'asc'],
                'relations' => ['user_id' => $user, 'category_id' => [AdCategory::class, 'name'], 'city_id' => $city],
                'filters' => ['status' => 'eq', 'type' => 'eq', 'category_id' => 'eq', 'city_id' => 'eq', 'user_id' => 'eq', 'created_at' => 'daterange'],
                'fields' => [
                    'user_id' => 'relation!', 'type' => 'select!', 'title' => 'text!', 'description' => 'text', 'price' => 'number',
                    'category_id' => 'relation!', 'city_id' => 'relation!', 'additional_phone' => 'text', 'status' => 'select!', 'reject_reason' => 'text',
                ],
                'beforeSave' => function (Advert $a, array &$data) {
                    $data['price'] ??= 0;
                    $data['status'] ??= Advert::STATUS_MODERATE;
                },
                // Автор получает пуш, как при модерации заказа (OrderActivatedListener).
                'action' => function (string $key, Advert $ad, string $input) use ($push) {
                    $approve = $key === 'approve';
                    $ad->forceFill(['status' => $approve ? Advert::STATUS_ACTIVE : Advert::STATUS_REJECTED, 'reject_reason' => $approve ? null : ($input ?: null)])->save();
                    if ($ad->user) {
                        $push()->send(
                            $ad->user,
                            'Объявление «' . mb_substr($ad->title, 0, 60) . '»',
                            $approve ? 'Ваше объявление прошло модерацию и опубликовано' : 'Объявление отклонено' . ($input ? ": $input" : ''),
                            ['type' => $approve ? 'advert_activated' : 'advert_rejected', 'advert_id' => (string) $ad->id],
                        );
                    }

                    return null;
                },
            ],

            'ad-category' => [
                'model' => AdCategory::class,
                'search' => ['name'], 'defaultSort' => ['name', 'asc'],
                'relations' => ['parent_id' => [AdCategory::class, 'name', ['parent_id' => 0]]],
                'filters' => [
                    'level' => fn (Builder $q, string $v) => $q->where('parent_id', $v === 'root' ? '=' : '!=', 0),
                    'parent_id' => 'eq',
                ],
                'fields' => ['name' => 'text!', 'parent_id' => 'relation'],
                'beforeSave' => function (AdCategory $c, array &$data) {
                    $data['parent_id'] ??= 0;
                },
            ],

            'subscription' => [
                'model' => Subscription::class,
                'filters' => ['type' => 'eq'],
                'fields' => ['type' => 'select!', 'validity' => 'number!', 'price' => 'number!', 'apple_product_id' => 'text', 'google_product_id' => 'text'],
            ],

            'invoice' => [
                'model' => Invoice::class,
                'create' => false,
                'relations' => ['subscription_id' => [Subscription::class, fn (Subscription $s) => '(' . ($s->type === 'executor' ? 'Исполнитель' : 'Магазин') . ") $s->validity мес. $s->price тг."]],
                'filters' => [
                    'status' => 'eq', 'payment_method' => 'eq', 'invoiceable_type' => 'eq', 'subscription_id' => 'eq',
                    'active' => fn (Builder $q, string $v) => $v === 'yes'
                        ? $q->where('status', Invoice::STATUS_PAID)->where('expired_at', '>', $today())
                        : $q->where(fn ($w) => $w->where('status', '!=', Invoice::STATUS_PAID)->orWhereNull('expired_at')->orWhere('expired_at', '<=', $today())),
                    'expired_at' => 'daterange',
                    'created_at' => 'daterange',
                ],
                'fields' => ['status' => 'select!', 'expired_at' => 'date'],
                'listExtra' => fn (Collection $rows) => ['morph' => $morph($rows, 'invoiceable_type', 'invoiceable_id')],
                // Как InvoiceService::paid: срок от конца текущей оплаченной подписки владельца или от сегодня.
                'beforeSave' => function (Invoice $inv, array &$data) use ($today) {
                    if (!$inv->exists || $data['status'] !== Invoice::STATUS_PAID || !empty($data['expired_at'])) {
                        return;
                    }
                    $validity = $inv->subscription?->validity;
                    if (!$validity) {
                        return;
                    }
                    $current = Invoice::where('invoiceable_type', $inv->invoiceable_type)->where('invoiceable_id', $inv->invoiceable_id)
                        ->where('id', '!=', $inv->id)->where('status', Invoice::STATUS_PAID)->where('expired_at', '>', $today())
                        ->orderByDesc('expired_at')->value('expired_at');
                    $data['expired_at'] = Carbon::parse($current ?: $today())->addMonths((int) $validity)->toDateString();
                },
            ],

            'setting' => [
                'model' => Setting::class,
                'create' => false, 'delete' => false,
                'fields' => ['value' => 'boolean'],
                'beforeSave' => function (Setting $s, array &$data) {
                    $data['value'] = $data['value'] ? '1' : '0';
                },
            ],

            'order-category' => [
                'model' => OrderCategory::class,
                'search' => ['title'], 'defaultSort' => ['title', 'asc'],
                'relations' => ['parent_id' => [OrderCategory::class, 'title', ['parent_id' => 0]]],
                'filters' => [
                    'level' => fn (Builder $q, string $v) => $v === 'root'
                        ? $q->where(fn ($w) => $w->where('parent_id', 0)->orWhereNull('parent_id'))
                        : $q->where('parent_id', '>', 0),
                    'parent_id' => 'eq',
                ],
                'fields' => ['title' => 'text!', 'parent_id' => 'relation'],
                'beforeSave' => function (OrderCategory $c, array &$data) {
                    $data['parent_id'] ??= 0;
                },
            ],

            // Статус меняется формой; переход «на проверке → активный» ловит
            // OrderObserver и шлёт автору пуш.
            'order' => [
                'model' => Order::class,
                'search' => ['title'],
                'relations' => ['user_id' => $user, 'category_id' => [OrderCategory::class, 'title'], 'city_id' => $city, 'executor_id' => [Executor::class, 'name']],
                'filters' => [
                    'status' => 'eq', 'category_id' => 'eq', 'city_id' => 'eq', 'user_id' => 'eq', 'executor_id' => 'eq',
                    'has_executor' => fn (Builder $q, string $v) => $v === 'yes'
                        ? $q->where('executor_id', '>', 0)
                        : $q->where(fn ($w) => $w->whereNull('executor_id')->orWhere('executor_id', 0)),
                    'offers' => fn (Builder $q, string $v) => $q->{$v === 'yes' ? 'whereHas' : 'whereDoesntHave'}('offers'),
                    'created_at' => 'daterange',
                ],
                'fields' => [
                    'title' => 'text!', 'description' => 'text', 'price_recommended' => 'number', 'price_max' => 'number', 'execution_days' => 'number',
                    'category_id' => 'relation!', 'status' => 'select!', 'user_id' => 'relation!', 'city_id' => 'relation!', 'executor_id' => 'relation',
                ],
            ],

            'order-offer' => [
                'model' => OrderOffer::class,
                'search' => ['comment', 'price'],
                'relations' => ['order_id' => [Order::class, fn (Order $o) => "№$o->id — $o->title"], 'user_id' => $user, 'city_id' => $city],
                'filters' => [
                    'user_id' => 'eq', 'city_id' => 'eq', 'price_type' => 'eq',
                    'order_status' => fn (Builder $q, string $v) => $q->whereHas('order', fn ($o) => $o->where('status', (int) $v)),
                    // Принят = заказу назначен исполнитель с user_id автора отклика.
                    'accepted' => fn (Builder $q, string $v) => $q->{$v === 'yes' ? 'whereExists' : 'whereNotExists'}(
                        fn ($s) => $s->from('orders')->join('executors', 'executors.id', '=', 'orders.executor_id')
                            ->whereColumn('orders.id', 'order_offers.order_id')->whereColumn('executors.user_id', 'order_offers.user_id')
                    ),
                    'expired_at' => 'daterange',
                    'created_at' => 'daterange',
                ],
                'fields' => [
                    'order_id' => 'relation!', 'user_id' => 'relation!', 'city_id' => 'relation!', 'price' => 'text!',
                    'price_type' => 'select', 'date' => 'text!', 'comment' => 'text', 'expired_at' => 'date',
                ],
            ],

            'support-ticket' => [
                'model' => SupportTicket::class,
                'create' => false,
                'search' => ['message', 'admin_reply'], 'defaultSort' => ['created_at', 'desc'],
                'relations' => ['user_id' => $userPhone],
                'filters' => [
                    'status' => 'eq', 'user_id' => 'eq',
                    'attachments' => fn (Builder $q, string $v) => $v === 'yes'
                        ? $q->whereNotNull('attachments')->where('attachments', '!=', '[]')
                        : $q->where(fn ($w) => $w->whereNull('attachments')->orWhere('attachments', '[]')),
                    'replied_at' => 'daterange',
                    'created_at' => 'daterange',
                ],
                'fields' => ['message' => 'readonly', 'admin_reply' => 'text', 'status' => 'select!'],
                // Новый или изменённый ответ: replied_at, статус «Отвечено», пуш автору.
                'beforeSave' => function (SupportTicket $t, array &$data, array &$raw) {
                    if (!empty($data['admin_reply']) && $data['admin_reply'] !== $t->admin_reply) {
                        $data['replied_at'] = now();
                        if ($data['status'] === SupportTicket::STATUS_OPEN) {
                            $data['status'] = SupportTicket::STATUS_ANSWERED;
                        }
                        $raw['notify'] = true;
                    }
                },
                'afterSave' => function (SupportTicket $t, array $raw) use ($push) {
                    if (!empty($raw['notify']) && $t->user) {
                        $push()->send($t->user, 'Ответ поддержки', mb_substr($t->admin_reply, 0, 200), ['type' => 'support_reply', 'ticket_id' => (string) $t->id]);
                    }
                },
            ],

            'report' => [
                'model' => Report::class,
                'create' => false, 'edit' => false,
                'search' => ['comment'], 'defaultSort' => ['created_at', 'desc'],
                'relations' => ['user_id' => $userPhone, 'reporter_id' => $user],
                'filters' => [
                    'status' => 'eq', 'resolution' => 'eq', 'reason' => 'eq', 'target_type' => 'eq', 'user_id' => 'eq', 'reporter_id' => 'eq',
                    'banned' => fn (Builder $q, string $v) => $q->{$v === 'yes' ? 'whereIn' : 'whereNotIn'}('user_id', User::withTrashed()->whereNotNull('banned_at')->select('id')),
                    'created_at' => 'daterange',
                ],
                'listExtra' => fn (Collection $rows) => ['banned' => User::withTrashed()->whereIn('id', $rows->pluck('user_id')->unique())
                    ->get(['id', 'banned_at'])->mapWithKeys(fn (User $u) => [(string) $u->id => $u->isBanned()])->all()],
                'action' => function (string $key, Report $rep, string $input, int $adminId) {
                    if ($rep->status !== Report::STATUS_OPEN) {
                        return 'Жалоба уже обработана';
                    }
                    $ban = $key === 'ban';
                    $rep->resolve($ban ? Report::RESOLUTION_BANNED : Report::RESOLUTION_DISMISSED, $adminId);
                    if ($ban) {
                        $rep->user?->ban($input ?: "Жалоба #$rep->id: " . $rep->getReasonName());
                    }

                    return null;
                },
            ],

            'user-block' => [
                'model' => UserBlock::class,
                'create' => false, 'edit' => false,
                'defaultSort' => ['created_at', 'desc'],
                'relations' => ['user_id' => $userPhone, 'blocked_user_id' => $userPhone],
                'filters' => ['user_id' => 'eq', 'blocked_user_id' => 'eq', 'created_at' => 'daterange'],
            ],
        ];
    }
}
