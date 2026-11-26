<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\OrderCategory;
use App\Models\Service;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\MakesSubscribers;
use Tests\TestCase;

/**
 * Приложение должно знать о блокировке до нажатия на кнопку: сервер отдаёт
 * флаги blocked_by_me / blocked_me вместе с пользователем и прячет телефон,
 * пока между людьми стоит блокировка в любую сторону.
 */
class BlockFlagsTest extends TestCase
{
    use RefreshDatabase, MakesSubscribers;

    private function as(User $user)
    {
        $this->app['auth']->forgetGuards();
        return $this->withToken($user->createToken('api')->plainTextToken);
    }

    private function serviceOf(User $author): Service
    {
        $s = Service::create(['user_id' => $author->id, 'city_id' => $author->city_id, 'title' => 't',
            'additional_phone' => '+77012345678', 'status' => Service::STATUS_ACTIVE]);
        $s->categories()->sync([OrderCategory::create(['title' => 'X', 'parent_id' => 0])->id]);
        return $s;
    }

    public function test_without_block_flags_are_false_and_phone_is_visible(): void
    {
        $author = $this->makeUser('+77001000001');
        $viewer = $this->makeUser('+77001000002');
        $s = $this->serviceOf($author);

        $this->as($viewer)->getJson("/api/services/$s->id")->assertOk()
            ->assertJsonPath('service.additional_phone', '+77012345678')
            ->assertJsonPath('service.user.blocked_by_me', false)
            ->assertJsonPath('service.user.blocked_me', false);
    }

    public function test_viewer_blocked_author_hides_phone_and_sets_blocked_by_me(): void
    {
        $author = $this->makeUser('+77001000001');
        $viewer = $this->makeUser('+77001000002');
        $s = $this->serviceOf($author);
        UserBlock::create(['user_id' => $viewer->id, 'blocked_user_id' => $author->id]);

        $this->as($viewer)->getJson("/api/services/$s->id")->assertOk()
            ->assertJsonPath('service.additional_phone', null)
            ->assertJsonPath('service.user.phone', null)
            ->assertJsonPath('service.user.blocked_by_me', true)
            ->assertJsonPath('service.user.blocked_me', false);
    }

    public function test_author_blocked_viewer_hides_phone_and_sets_blocked_me(): void
    {
        $author = $this->makeUser('+77001000001');
        $viewer = $this->makeUser('+77001000002');
        $s = $this->serviceOf($author);
        UserBlock::create(['user_id' => $author->id, 'blocked_user_id' => $viewer->id]);

        $this->as($viewer)->getJson("/api/services/$s->id")->assertOk()
            ->assertJsonPath('service.additional_phone', null)
            ->assertJsonPath('service.user.blocked_by_me', false)
            ->assertJsonPath('service.user.blocked_me', true);
    }

    public function test_chat_list_companion_carries_flags_and_block_is_fresh_within_request_flow(): void
    {
        $me = $this->makeUser('+77001000001');
        $other = $this->makeUser('+77001000002');
        $chat = Chat::create();
        $chat->members()->attach([$me->id, $other->id]);
        // Список чатов показывает только переписки с сообщениями.
        ChatMessage::create(['chat_id' => $chat->id, 'user_id' => $other->id, 'message' => 'Привет']);

        $this->as($me)->getJson('/api/chat')->assertOk()->assertJsonPath('list.0.companion.blocked_by_me', false);

        $this->as($me)->postJson('/api/blocks', ['user_id' => $other->id])->assertOk();
        $this->as($me)->getJson('/api/chat')->assertOk()->assertJsonPath('list.0.companion.blocked_by_me', true);
        $this->as($other)->getJson('/api/chat')->assertOk()->assertJsonPath('list.0.companion.blocked_me', true);
    }
}
