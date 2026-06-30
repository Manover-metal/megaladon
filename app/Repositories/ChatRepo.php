<?php

namespace App\Repositories;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatUser;

class ChatRepo
{
    public function createChat()
    {
        return Chat::create([]);
    }

    public function getChatIdsByUserId(int $userId)
    {
        return ChatUser::where('user_id', $userId)
            ->pluck('chat_id')
            ->toArray();
    }

    public function index(array $chatIds)
    {
        return Chat::with('chatable')
            ->whereIn('id', $chatIds)
            ->get();
    }

    public function indexMessages(int $chatId, array $params)
    {
        $query = ChatMessage::with('user')
            ->where('chat_id', $chatId);
        $query = $this->applyPaginationQuery($query, $params);
        $query = $this->applySortBy($query, $params);
        return $query->get();
    }

    public function storeChatMessage(array $data)
    {
        return ChatMessage::create($data);
    }

    public function editMessage(ChatMessage $chatMessage, array $data)
    {
        return $chatMessage->update($data);
    }

    public function deleteMessage($message_id)
    {
        ChatMessage::find($message_id)->delete();
    }

    private function applyPaginationQuery($query, $params)
    {
        if (isset($params['startRow'])) {
            $query->skip($params['startRow']);
        }
        if (isset($params['rowsPerPage'])) {
            $query->take($params['rowsPerPage']);
        } else {
            $query->take(100);
        }
        return $query;
    }

    private function applySortBy($query, $params)
    {
        $desc = 'ASC';

        if (isset($params['desc'])) {
            $desc = (boolean)$params['desc'] ? 'DESC' : 'ASC';
        }

        if (isset($params['sortBy'])) {
            $query->orderBy($params['sortBy'], $desc);
        }

        return $query;
    }
}