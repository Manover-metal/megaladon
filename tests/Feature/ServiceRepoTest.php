<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\OrderCategory;
use App\Models\Service;
use App\Models\User;
use App\Presenters\v1\ServicePresenter;
use App\Repositories\ServiceRepo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRepoTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $phone = '+77001112233'): User
    {
        return User::create(['name' => 'U', 'phone' => $phone, 'password' => 'secret123', 'is_phone_confirmed' => true,
            'city_id' => City::firstOrCreate(['name' => 'Алматы'])->id]);
    }

    public function test_index_filters_by_order_category_branch_and_only_active(): void
    {
        $user = $this->user();
        $city = City::firstOrCreate(['name' => 'Алматы']);
        $root = OrderCategory::create(['title' => 'Резка', 'parent_id' => 0]);
        $leaf = OrderCategory::create(['title' => 'Лазерная', 'parent_id' => $root->id]);
        $other = OrderCategory::create(['title' => 'Сварка', 'parent_id' => 0]);
        $base = ['user_id' => $user->id, 'city_id' => $city->id, 'title' => 't'];
        $a = Service::create($base + ['category_id' => $leaf->id, 'status' => Service::STATUS_ACTIVE]);
        Service::create($base + ['category_id' => $other->id, 'status' => Service::STATUS_ACTIVE]);
        Service::create($base + ['category_id' => $leaf->id, 'status' => Service::STATUS_MODERATE]);

        $ids = (new ServiceRepo())->index(['category' => $root->id, 'only_active' => true])->pluck('id')->all();

        $this->assertSame([$a->id], $ids);
    }

    public function test_presenter_has_no_type_and_uses_order_category_title(): void
    {
        $user = $this->user();
        $leaf = OrderCategory::create(['title' => 'Лазерная', 'parent_id' => 0]);
        $s = Service::create(['user_id' => $user->id, 'city_id' => $user->city_id, 'title' => 't', 'category_id' => $leaf->id, 'price' => 100]);

        $list = (new ServicePresenter((new ServiceRepo())->info($s->id)))->list();

        $this->assertArrayNotHasKey('type', $list);
        $this->assertSame('Лазерная', $list['category']['name']);
        $this->assertSame(1, $list['status']);
    }
}
