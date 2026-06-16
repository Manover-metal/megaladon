<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            'ИП',
            'ТОО',
            'АО',
            'ООО',
        ];

        $now = now();

        foreach ($types as $title) {
            DB::table('company_types')->updateOrInsert(
                ['title' => $title],
                ['updated_at' => $now, 'created_at' => $now, 'deleted_at' => null]
            );
        }
    }
}
