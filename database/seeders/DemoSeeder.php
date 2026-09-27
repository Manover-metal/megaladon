<?php

namespace Database\Seeders;

use App\Models\AdCategory;
use App\Models\Advert;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\City;
use App\Models\Executor;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderOffer;
use App\Models\Rating;
use App\Models\ServiceType;
use App\Models\Store;
use App\Models\StoreContacts;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Витринные данные для скриншотов приложения: металлообработка, реальные
 * города, цены в тенге. Рассчитан на пустую базу:
 *
 *   php artisan migrate:fresh --seed
 *   php artisan db:seed --class=DemoSeeder
 *
 * В общий DatabaseSeeder не входит — на проде таким данным не место.
 * Вход под заказчиком: +77010000001 / demo12345.
 */
class DemoSeeder extends Seeder
{
    const PASSWORD = 'demo12345';

    private array $cities;

    public function run()
    {
        if (app()->environment('production')) {
            $this->command->error('DemoSeeder не запускается на production.');
            return;
        }

        $this->cities = City::pluck('id', 'name')->all();
        $this->copyImages();

        // Базовые сидеры заводят бытовые категории («Уборка», «Репетиторство»),
        // для витрины металлообработки подменяем их своими.
        DB::table('order_categories')->delete();
        DB::table('service_types')->delete();
        DB::table('ad_categories')->delete();

        $cats = $this->orderCategories();
        $services = $this->serviceTypes();
        $adCats = $this->adCategories();

        $plans = [
            Subscription::EXECUTOR => Subscription::create(['type' => Subscription::EXECUTOR, 'validity' => 12, 'price' => 60000]),
            Subscription::STORE => Subscription::create(['type' => Subscription::STORE, 'validity' => 12, 'price' => 90000]),
        ];

        // Заказчик, от лица которого снимаются экраны.
        $me = $this->user('Данияр Сапаров', '+77010000001', 'Алматы');

        $customers = [
            $this->user('Ерлан Мукашев', '+77010000002', 'Астана'),
            $this->user('Айгерим Нурланова', '+77010000003', 'Алматы'),
            $this->user('Сергей Ким', '+77010000004', 'Караганды'),
            $this->user('Асель Жумабаева', '+77010000005', 'Шымкент'),
        ];

        $executors = [];
        foreach ([
            ['МеталлПро', 'Алматы', 'Лазерная и плазменная резка листа до 20 мм, гибка на листогибе 3 м, сварка. Работаем по чертежам DXF/DWG, доставка по городу.', 'г. Алматы, ул. Бекмаханова, 93', [0, 1, 4, 6], 'laser'],
            ['СтальЦех', 'Алматы', 'Металлоконструкции под ключ: каркасы, навесы, лестницы, ограждения. Своя покрасочная камера, порошковая покраска.', 'г. Алматы, мкр. Алгабас, 1/12', [5, 6, 7, 8], 'weld'],
            ['ЛазерТех', 'Астана', 'Оптоволоконный лазер 6 кВт, раскрой нержавейки и алюминия. Срочные заказы за 24 часа.', 'г. Астана, ул. Жангельдина, 22', [0, 4], 'bending'],
            ['ТокарьКЗ', 'Караганды', 'Токарные и фрезерные работы на станках с ЧПУ. Валы, втулки, фланцы, шестерни — от единицы до серии.', 'г. Караганда, ул. Складская, 8', [2, 3], 'lathe'],
            ['АргонСвар', 'Шымкент', 'Аргонодуговая сварка нержавейки, алюминия и титана. Выезд на объект.', 'г. Шымкент, ул. Капал Батыра, 5', [5, 6], 'grinding'],
        ] as $i => [$name, $city, $about, $address, $svc, $photo]) {
            $user = $this->user($name, '+7702000000' . ($i + 1), $city, $photo);
            $executor = Executor::create([
                'user_id' => $user->id,
                'name' => $name,
                'description' => $about,
                'bin' => '1508400' . sprintf('%05d', 10231 + $i * 7),
                'lat' => 43.24,
                'lon' => 76.89,
                'full_address' => $address,
            ]);
            $executor->services()->sync(array_map(fn ($k) => $services[$k], $svc));
            $this->paid($executor, $plans[Subscription::EXECUTOR]);
            $executors[] = $executor;
        }

        // Лента заказов. Первые два — мои, у первого пачка откликов.
        $orders = [];
        foreach ([
            [$me, 'Лазерная резка листа 4 мм, 120 деталей', 'Сталь 09Г2С, лист 4 мм. 120 фланцев по чертежу, допуск ±0,2 мм. Металл наш, нужна только резка и зачистка кромок.', 'Лазерная резка', 'Алматы', 180000, 165000, 5, 'laser', 0],
            [$me, 'Лестница на второй этаж с поворотом', 'Каркас из профильной трубы 80×40, ступени под дерево, поворот на 90°. Высота 3,1 м. Нужен замер и монтаж.', 'Лестницы и ограждения', 'Алматы', 650000, 580000, 14, 'stairs', 0],
            [$customers[0], 'Сварка каркаса навеса 6×4 м', 'Навес для автомобиля: стойки из трубы 100×100, фермы из профиля 60×40. Порошковая покраска в графит.', 'Каркасы и навесы', 'Астана', 420000, 390000, 10, 'frame', 0],
            [$customers[1], 'Токарная обработка валов, 30 шт', 'Вал Ø45, длина 320 мм, сталь 45. Две шпоночные канавки, резьба М24. Чертёж приложу в чате.', 'Токарные работы', 'Алматы', 240000, 210000, 7, 'lathe', 0],
            [$customers[2], 'Фрезеровка корпусов на ЧПУ', 'Алюминий Д16Т, 15 корпусов 120×80×40 мм. 3D-модель STEP есть.', 'Фрезерные работы', 'Караганды', 310000, 280000, 12, 'cnc-part', 0],
            [$customers[3], 'Аргонная сварка баков из нержавейки', 'Бак 500 л, AISI 304, толщина 2 мм. Швы герметичные, опрессовка.', 'Аргонная сварка', 'Шымкент', 150000, 135000, 4, 'weld', 0],
        ] as $i => [$owner, $title, $desc, $cat, $city, $max, $rec, $days, $img, $status]) {
            $order = Order::create([
                'user_id' => $owner->id,
                'title' => $title,
                'description' => $desc,
                'price_max' => $max,
                'price_recommended' => $rec,
                'execution_days' => $days,
                'category_id' => $cats[$cat],
                'city_id' => $this->cities[$city],
                'executor_id' => 0,
                'status' => Order::STATUS_ACTIVE,
            ]);
            $order->media()->create(['storage_link' => "/storage/order/demo-$img.jpg"]);
            $this->age($order, Carbon::now()->subHours(3 + $i * 7));
            $orders[] = $order;
        }

        $offers = [
            [0, 0, '165000', '5 дней', 'Режем на оптоволокне, кромка без окалины. Можем забрать металл сами.'],
            [0, 2, '158000', '3 дня', 'Сделаем за 3 дня, отправим в Алматы транспортной за наш счёт.'],
            [0, 1, '175000', '4 дня', 'Дополнительно можем снять фаски и покрасить.'],
            [0, 4, '190000', '6 дней', null],
            [1, 1, '590000', '12 дней', 'Замер бесплатно, покажем похожие работы на объекте.'],
            [1, 0, '620000', '14 дней', null],
            [2, 1, '395000', '9 дней', 'Порошковая покраска в стоимости.'],
            [3, 3, '7000', '6 дней', 'Цена за один вал, металл наш.', OrderOffer::PRICE_TYPE_PER_UNIT],
            [4, 3, '270000', '10 дней', 'Есть пятиосевой станок, обработка за один установ.'],
            [5, 4, '140000', '4 дня', 'Опрессовка входит в стоимость.'],
        ];
        foreach ($offers as $j => $o) {
            [$oi, $ei, $price, $date, $comment] = $o;
            $offer = OrderOffer::create([
                'order_id' => $orders[$oi]->id,
                'user_id' => $executors[$ei]->user_id,
                'city_id' => $orders[$oi]->city_id,
                'price' => $price,
                'price_type' => $o[5] ?? OrderOffer::PRICE_TYPE_TOTAL,
                'date' => $date,
                'comment' => $comment,
            ]);
            $this->age($offer, Carbon::now()->subMinutes(20 + $j * 35));
        }

        // Выполненные заказы с отзывами — для рейтингов исполнителей.
        foreach ([
            [0, 'Раскрой листа 3 мм, 40 деталей', 'Сталь Ст3, 40 косынок по DXF.', 5, 'Всё ровно по чертежу, забрали металл сами. Рекомендую.'],
            [0, 'Гибка коробов из оцинковки', 'Оцинковка 0,7 мм, 25 коробов.', 5, 'Быстро и аккуратно, углы точные.'],
            [0, 'Резка заготовок под фланцы', 'Лист 10 мм, 60 заготовок.', 4.5, 'Хорошее качество, на день позже срока.'],
            [1, 'Навес над входом', 'Козырёк 3×1,5 м с поликарбонатом.', 5, 'Сделали и смонтировали за неделю, покраска отличная.'],
            [2, 'Резка нержавейки 2 мм', 'AISI 430, 80 деталей.', 5, 'Идеальная кромка, отправили в тот же день.'],
            [3, 'Втулки бронзовые, 50 шт', 'БрАЖ9-4, Ø40/25, длина 60.', 5, 'Размеры в допуске, упаковка на совесть.'],
            [4, 'Сварка перил из нержавейки', 'Перила 12 м на лестнице.', 4.5, 'Швы чистые, мастер приехал вовремя.'],
        ] as $k => [$ei, $title, $desc, $rate, $comment]) {
            $customer = $k % 2 ? $customers[$k % 4] : $me;
            $order = Order::create([
                'user_id' => $customer->id,
                'title' => $title,
                'description' => $desc,
                'price_max' => 100000,
                'category_id' => $cats['Лазерная резка'],
                'city_id' => $this->cities['Алматы'],
                'executor_id' => $executors[$ei]->id,
                'status' => Order::STATUS_COMPLETED,
            ]);
            $this->age($order, Carbon::now()->subDays(20 + $k * 9));
            $this->rate($customer, $executors[$ei], $rate, $comment, Carbon::now()->subDays(10 + $k * 9));
        }

        // Каталог металлопроката: без оплаченной подписки магазин не виден.
        foreach ([
            ['КазМеталл', 'Алматы', 'г. Алматы, ул. Рыскулова, 57', 5, '+77273100100', 'kazmetall.kz', 'scaffold'],
            ['СтальБаза', 'Астана', 'г. Астана, пр. Абая, 104', 5, '+77172550201', 'stalbaza.kz', 'rebar'],
            ['ПрокатСервис', 'Шымкент', 'г. Шымкент, ул. Толе би, 26', 4, '+77252330044', null, 'frame'],
            ['МеталлТорг', 'Караганды', 'г. Караганда, ул. Бытовая, 3', 5, '+77212410077', 'metalltorg.kz', 'sheet'],
        ] as $i => [$name, $city, $address, $rating, $phone, $site, $photo]) {
            $user = $this->user($name, '+7703000000' . ($i + 1), $city, $photo);
            $store = Store::create([
                'user_id' => $user->id,
                'name' => $name,
                'bin' => '0906400' . sprintf('%05d', 20417 + $i * 13),
                'city_id' => $this->cities[$city],
                'full_address' => $address,
            ]);
            StoreContacts::create(['store_id' => $store->id, 'type' => 'phone', 'contact_name' => 'Отдел продаж', 'value' => $phone]);
            if ($site) {
                StoreContacts::create(['store_id' => $store->id, 'type' => 'site', 'value' => $site]);
            }
            $this->paid($store, $plans[Subscription::STORE]);
            $this->rate($customers[$i], $store, $rating, 'Нужный прокат всегда в наличии, режут в размер.', Carbon::now()->subDays(5 + $i));
        }

        // Торговая площадка: объявления и услуги.
        foreach ([
            [Advert::TYPE_ADVERT, $customers[1], 'Лист х/к 1,5 мм, 1250×2500', 'Остатки со склада, 2,4 т. Самовывоз или доставка по Алматы.', 'Металлопрокат', 'Алматы', 385, 'sheet'],
            [Advert::TYPE_ADVERT, $customers[2], 'Токарный станок 16К20, после ремонта', 'РМЦ 1000 мм, полный комплект патронов, в работе. Можно проверить на месте.', 'Станки и оборудование', 'Караганды', 2400000, 'lathe'],
            [Advert::TYPE_ADVERT, $customers[3], 'Арматура А500С Ø12, 3 т', 'Остаток после стройки, хранилась под навесом. Цена за тонну.', 'Металлопрокат', 'Шымкент', 285000, 'rebar'],
            [Advert::TYPE_SERVICE, $executors[0]->user, 'Гибка листового металла до 3 м', 'Листогиб с ЧПУ, сталь до 6 мм, нержавейка до 4 мм. Изготовим короба, уголки, отливы.', 'Услуги металлообработки', 'Алматы', null, 'bending'],
            [Advert::TYPE_SERVICE, $executors[3]->user, 'Фрезеровка деталей на ЧПУ', 'Сталь, алюминий, латунь. Работаем по чертежам и 3D-моделям.', 'Услуги металлообработки', 'Караганды', 15000, 'cnc-parts'],
            [Advert::TYPE_SERVICE, $executors[1]->user, 'Перила и ограждения из нержавейки', 'Изготовление и монтаж под ключ, от 25 000 ₸ за погонный метр.', 'Услуги металлообработки', 'Алматы', 25000, 'railing'],
            [Advert::TYPE_SERVICE, $executors[4]->user, 'Резка и зачистка металла на выезде', 'Болгарка, бензорез, плазма. Демонтаж металлоконструкций.', 'Услуги металлообработки', 'Шымкент', null, 'grinding'],
        ] as $i => [$type, $owner, $title, $desc, $cat, $city, $price, $img]) {
            $ad = Advert::create([
                'type' => $type,
                'user_id' => $owner->id,
                'city_id' => $this->cities[$city],
                'title' => $title,
                'description' => $desc,
                'price' => $price,
                'category_id' => $adCats[$cat],
            ]);
            $ad->media()->create(['storage_link' => "/storage/advert/demo-$img.jpg"]);
            $this->age($ad, Carbon::now()->subHours(2 + $i * 5));
        }

        // Переписка по первому заказу.
        $this->chat($me, $executors[0]->user, [
            [false, 'Здравствуйте! Посмотрели чертёж — фланцы режем за 5 дней.', 180],
            [true, 'Добрый день. Кромку зачищаете или нужно отдельно?', 170],
            [false, 'Зачистка входит в цену. Можем снять фаски 1×45°, +8 000 ₸.', 160],
            [true, 'Фаски нужны. Металл привезу завтра до обеда.', 95],
            [false, 'Отлично, ждём. Адрес: Бекмаханова, 93, ворота №2.', 90],
            [true, 'Скинул уточнённый DXF, там два отверстия поменялись.', 12],
            [false, 'Получили, всё учли 👍', 5],
        ]);
        $this->chat($me, $executors[1]->user, [
            [false, 'Добрый день! Когда удобно подъехать на замер лестницы?', 300],
            [true, 'В субботу после 11:00.', 240],
        ]);
    }

    private function user(string $name, string $phone, string $city, ?string $photo = null): User
    {
        $user = User::create([
            'name' => $name,
            'phone' => $phone,
            'password' => self::PASSWORD,
            'is_phone_confirmed' => 1,
            'city_id' => $this->cities[$city],
        ]);
        if ($photo) {
            // Мутатор photo_url ждёт UploadedFile, поэтому пишем путь в обход него.
            DB::table('users')->where('id', $user->id)->update(['photo_url' => "/storage/users/demo-$photo.jpg"]);
        }
        return $user;
    }

    private function paid($model, Subscription $plan): void
    {
        $model->invoices()->create([
            'subscription_id' => $plan->id,
            'status' => Invoice::STATUS_PAID,
            'expired_at' => Carbon::now()->addYear()->toDateString(),
        ]);
    }

    private function rate(User $author, $target, float $rate, string $comment, Carbon $at): void
    {
        // ratingable_* нет в fillable; средний rating пересчитает RatingObserver.
        $rating = new Rating(['user_id' => $author->id, 'rate' => $rate, 'comment' => $comment]);
        $rating->ratingable_type = get_class($target);
        $rating->ratingable_id = $target->id;
        $rating->save();
        $this->age($rating, $at);
    }

    private function chat(User $me, User $other, array $messages): void
    {
        $chat = Chat::create();
        $chat->members()->attach([$me->id, $other->id]);
        foreach ($messages as [$mine, $text, $minutesAgo]) {
            $msg = ChatMessage::create([
                'chat_id' => $chat->id,
                'user_id' => $mine ? $me->id : $other->id,
                'message' => $text,
            ]);
            $msg->is_readed = true;
            $msg->save();
            $this->age($msg, Carbon::now()->subMinutes($minutesAgo));
        }
    }

    private function age($model, Carbon $at): void
    {
        DB::table($model->getTable())->where('id', $model->id)->update(['created_at' => $at, 'updated_at' => $at]);
    }

    private function orderCategories(): array
    {
        $ids = [];
        foreach ([
            'Металлообработка' => ['Лазерная резка', 'Плазменная резка', 'Токарные работы', 'Фрезерные работы', 'Гибка металла'],
            'Сварочные работы' => ['Аргонная сварка', 'Полуавтомат', 'Сварка на выезде'],
            'Металлоконструкции' => ['Лестницы и ограждения', 'Ворота и заборы', 'Каркасы и навесы'],
            'Покраска и покрытия' => ['Порошковая покраска', 'Цинкование'],
        ] as $parent => $children) {
            $parentId = OrderCategory::create(['title' => $parent, 'parent_id' => 0])->id;
            foreach ($children as $child) {
                $ids[$child] = OrderCategory::create(['title' => $child, 'parent_id' => $parentId])->id;
            }
        }
        return $ids;
    }

    private function serviceTypes(): array
    {
        return array_map(
            fn ($name) => ServiceType::create(['name' => $name])->id,
            ['Лазерная резка', 'Плазменная резка', 'Токарные работы', 'Фрезеровка ЧПУ', 'Гибка листа', 'Аргонная сварка', 'Сварка полуавтоматом', 'Металлоконструкции', 'Порошковая покраска']
        );
    }

    private function adCategories(): array
    {
        $ids = [];
        foreach (['Металлопрокат', 'Станки и оборудование', 'Инструмент', 'Услуги металлообработки'] as $name) {
            $ids[$name] = AdCategory::create(['name' => $name, 'parent_id' => 0])->id;
        }
        return $ids;
    }

    private function copyImages(): void
    {
        foreach (['order', 'advert', 'users'] as $dir) {
            File::ensureDirectoryExists(storage_path("app/public/$dir"));
        }
        foreach (File::files(database_path('seeders/demo')) as $file) {
            foreach (['order', 'advert', 'users'] as $dir) {
                File::copy($file->getPathname(), storage_path("app/public/$dir/demo-" . $file->getFilename()));
            }
        }
    }
}
