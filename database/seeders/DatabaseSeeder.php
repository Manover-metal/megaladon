<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CitySeeder::class);
        $this->call(AdvertCategorySeeder::class);
        $this->call(OrderCategorySeeder::class);
        $this->call(ServiceTypeSeeder::class);
        $this->call(CompanyTypeSeeder::class);
    }
}
