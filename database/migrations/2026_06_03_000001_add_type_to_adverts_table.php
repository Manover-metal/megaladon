<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToAdvertsTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('adverts', 'type')) {
            return;
        }

        Schema::table('adverts', function (Blueprint $table) {
            $table->string('type')->default('advert')->after('category_id');
        });
    }

    public function down()
    {
        Schema::table('adverts', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
