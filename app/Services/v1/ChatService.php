<?php

namespace App\Services\v1;

use App\Events\NewMessageEvent;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\UserBlock;
use App\Presenters\v1\ChatPresenter;
use App\Repositories\ChatRepo;
use App\Services\BaseService;
use Illuminate\Support\Facades\Storage;

class ChatService extends BaseService
{
    private ChatRepo $chatRepo;

    public function __construct() {
        $this->chatRepo = new ChatRepo();
    }

    public function getChats(User $user)
    {
        $chatIds = $this->chatRepo->getChatIdsByUserId($user->id);
        $chats = $this->chatRepo->index($chatIds, $user->id);

        return $this->resultCollections($chats, ChatPresenter::class, 'chatList');
    }

    // Пользователь открыл переписку: чужие сообщения перестают быть
    // непрочитанными, бейдж в списке и в меню гаснет.
    public function markRead(User $user, int $chatId)
    {
        $chat = Chat::find($chatId);

        if (is_null($chat)) {
            return $this->errNotFound(__('chat.chat_not_found'));
        }

        if (!$chat->members()->whereKey($user->id)->exists()) {
            return $this->errFobidden(__('chat.not_a_member'));
        }

        $this->chatRepo->markChatRead($chatId, $user->id);

        return $this->ok();
    }

    public function chatMessages(User $user, int $chatId, array $params)
    {
        $chat = Chat::find($chatId);

        if (is_null($chat)) {
            return $this->errNotFound(__('chat.chat_not_found'));
        }

        $messages = $this->chatRepo->indexMessages($chatId, $params);
        return $this->resultCollections($messages, ChatPresenter::class, 'messages');
    }

    public function sendMessage(User $user, array $data)
    {
        $companionId = Chat::find($data['chat_id'])?->members()->whereKeyNot($user->id)->value('users.id');
        if ($companionId && UserBlock::existsBetween($user->id, $companionId)) {
            return $this->errFobidden(__('chat.user_blocked'));
        }

        $data['user_id'] = $user->id;

        if (isset($data['file'])) {
            $data['file_url'] = Storage::url($data['file']->store('public/chat'));
        }

        $message = $this->chatRepo->storeChatMessage($data);
        $message = ChatMessage::find($message->id);
        broadcast(new NewMessageEvent($message, [$user->id]));
        return $this->result(['chat_message' => (new ChatPresenter($message))->messages()]);
    }

    public function editMessage(int $message_id, User $user, array $data)
    {
        $chatMessage = ChatMessage::find($message_id);

        if (is_null($chatMessage)) {
            return $this->errNotFound(__('chat.message_not_found'));
        }

        if ($chatMessage->user_id != $user->id) {
            return $this->error(406, __('chat.cannot_edit_foreign_message'));
        }

        $message = $this->chatRepo->editMessage($chatMessage, $data);

        return $this->result([
            'message' => __('chat.message_updated'),
            'data' => $message,
        ]);
    }

    public function deleteMessage(int $message_id, User $user)
    {
        $chatMessage = ChatMessage::find($message_id);

        if (is_null($chatMessage)) {
            return $this->errNotFound(__('chat.message_not_found'));
        }

        if ($chatMessage->user_id != $user->id) {
            return $this->error(406, __('chat.cannot_edit_foreign_message'));
        }

        $this->chatRepo->deleteMessage($message_id);

        return $this->ok(__('chat.message_deleted'));
    }

    public function createChat(User $author, int $companionId)
    {
        if ($author->id === $companionId) {
            return $this->errNotAcceptable(__('chat.cannot_chat_with_self'));
        }

        $companion = User::find($companionId);
        if (is_null($companion)) {
            return $this->errNotFound(__('chat.companion_not_found'));
        }

        if (UserBlock::existsBetween($author->id, $companionId)) {
            return $this->errFobidden(__('chat.user_blocked'));
        }

        $chat = $this->chatRepo->findByMembers($author->id, $companionId);

        if (is_null($chat)) {
            $chat = $this->chatRepo->createChat();
            $this->attachMembersToChat($chat, [$author->id, $companionId]);
            // О пустом чате собеседника не уведомляем: пока в нём нет
            // сообщений, он всё равно скрыт из списка (ChatRepo::index).
            // Про первое сообщение он узнает из NewMessageEvent.
        }

        return $this->result([
            'chat' => (new ChatPresenter($chat))->chatList($author->id),
        ]);
    }

    private function attachMembersToChat(Chat $chat, array $userIds)
    {
        foreach ($userIds as $id) {
            $chat->members()->attach(['user_id' => $id]);
        }
    }
}