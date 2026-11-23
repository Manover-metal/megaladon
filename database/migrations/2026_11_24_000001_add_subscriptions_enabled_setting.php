<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Временный выключатель платного доступа. Выключен — приложение прячет всё
 * про подписки, а бэкенд не требует подписку ни для услуг, ни для откликов и
 * контактов, ни для показа магазинов (см. Subscription::enabled()).
 * Заводится выключенным: подписки скрыты до решения заказчика.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insert([
            'key' => 'subscriptions_enabled',
            'value' => '0',
            'label' => 'Подписки включены (иначе доступ бесплатный, экраны подписок скрыты)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Пока подписки скрыты, ручная оплата не нужна, а старые версии
        // приложения создавали бы через неё заявки. И App Store, и Google Play
        // требуют продавать доступ к функциям только своими покупками —
        // включать обратно вместе с подписками не стоит.
        DB::table('settings')
            ->whereIn('key', ['manual_payment_ios', 'manual_payment_android'])
            ->update(['value' => '0', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'subscriptions_enabled')->delete();
        DB::table('settings')
            ->whereIn('key', ['manual_payment_ios', 'manual_payment_android'])
            ->update(['value' => '1', 'updated_at' => now()]);
    }
};
