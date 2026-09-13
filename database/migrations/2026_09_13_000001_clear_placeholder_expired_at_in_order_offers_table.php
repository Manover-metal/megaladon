<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ClearPlaceholderExpiredAtInOrderOffersTable extends Migration
{
    /**
     * Приложение до сих пор слало в каждом отклике заглушку
     * expired_at = 2021-12-12, и все отклики показывались «Истёк». Срок
     * отклика никто не задаёт — обнуляем заглушку у уже сохранённых.
     *
     * @return void
     */
    public function up()
    {
        DB::table('order_offers')
            ->whereDate('expired_at', '2021-12-12')
            ->update(['expired_at' => null]);
    }

    /**
     * Какие отклики были с заглушкой, уже не узнать — откатывать нечего.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
