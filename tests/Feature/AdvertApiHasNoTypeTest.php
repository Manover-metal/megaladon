<?php

namespace Tests\Feature;

use App\Models\AdCategory;
use App\Models\Advert;
use App\Models\City;
use App\Models\OrderCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvertApiHasNoTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_has_no_type_and_category_filter_uses_ad_tree_only(): void
    {
        $user = User::create(['name' => 'U', 'phone' => '+77001234567', 'password' => 'secret123', 'is_phone_confirmed' => true,
            'city_id' => City::firstOrCreate(['name' => 'Алматы'])->id]);
        $adCat = AdCategory::create(['name' => 'Станки']);
        // Та же цифра в дереве услуг — раньше фильтр ветвился по типу.
        OrderCategory::create(['title' => 'Другое', 'parent_id' => 0]);
        $a = Advert::create(['user_id' => $user->id, 'city_id' => $user->city_id, 'title' => 't', 'category_id' => $adCat->id, 'status' => Advert::STATUS_ACTIVE]);

        $list = $this->getJson("/api/adverts?category=$adCat->id")->assertOk()->json('list');

        $this->assertSame([$a->id], array_column($list, 'id'));
        $this->assertArrayNotHasKey('type', $list[0]);
        $this->assertSame('Станки', $list[0]['category']['name']);
    }
}
