<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Нет строки в order_views — нет и бейджа: заказы, созданные до таблицы,
     * не зажигали его никогда, сколько бы откликов ни пришло. Заводим строки
     * с текущим состоянием заказа — ничего не вспыхнет, но следующие
     * изменения начнут считаться. Стороны те же, что у markSeen в
     * OrderService: заказчик, откликнувшиеся и назначенный исполнитель.
     *
     * Тем же заходом заполняем seen_updated_at у строк старых релизов: при
     * null правки заказчика исполнителю не показывались вовсе.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            INSERT INTO order_views
                (user_id, order_id, seen_status, seen_offers_count, seen_updated_at, created_at, updated_at)
            SELECT p.user_id, o.id, o.status,
                   (SELECT COUNT(*) FROM order_offers oo WHERE oo.order_id = o.id),
                   o.updated_at, NOW(), NOW()
            FROM (
                SELECT id AS order_id, user_id FROM orders
                UNION
                SELECT order_id, user_id FROM order_offers
                UNION
                SELECT orders.id, executors.user_id
                FROM orders JOIN executors ON executors.id = orders.executor_id
            ) p
            JOIN orders o ON o.id = p.order_id
            WHERE p.user_id IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1 FROM order_views v
                  WHERE v.user_id = p.user_id AND v.order_id = p.order_id
              )
            SQL);

        DB::statement(<<<'SQL'
            UPDATE order_views
            SET seen_updated_at = (SELECT o.updated_at FROM orders o WHERE o.id = order_views.order_id)
            WHERE seen_updated_at IS NULL
            SQL);
    }

    // Отличить заведённые здесь строки от настоящих просмотров нельзя, а
    // лишняя строка вреда не несёт — откатывать нечего.
    public function down(): void
    {
    }
};
