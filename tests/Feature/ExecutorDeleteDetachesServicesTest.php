<?php

namespace Tests\Feature;

use App\Models\OrderCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\MakesSubscribers;
use Tests\TestCase;

/** Удалённый исполнитель не должен держать категории через осиротевшую связку. */
class ExecutorDeleteDetachesServicesTest extends TestCase
{
    use RefreshDatabase, MakesSubscribers;

    public function test_deleting_executor_clears_executor_service_types(): void
    {
        $executor = $this->makeExecutor($this->makeUser('+77001234567'));
        $executor->services()->sync([OrderCategory::create(['title' => 'Лазерная', 'parent_id' => 0])->id]);
        $this->assertSame(1, DB::table('executor_service_types')->where('executor_id', $executor->id)->count());

        $executor->delete();

        $this->assertSame(0, DB::table('executor_service_types')->where('executor_id', $executor->id)->count());
    }
}
