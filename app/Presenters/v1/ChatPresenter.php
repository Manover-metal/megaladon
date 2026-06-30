<?php

namespace App\Presenters\v1;

use App\Presenters\BasePresenter;

class ChatPresenter extends BasePresenter
{
    public function chatList()
    {
        $lastMessage = $this->lastMessage();
        return [
            'id' => $this->id,
            'title' => $this->chatable->title,
            'lastMessage' => is_null($lastMessage) ? [] : [
                'id' => $lastMessage->id,
                'user_id' => $lastMessage->user_id,
                'message' => $lastMessage->message,
                'file' => $lastMessage->file_url,
                'is_readed' => (boolean) $lastMessage->is_readed,
                'created_at' => $lastMessage->created_at,
            ],
        ];
    }

    public function messages()
    {
        return [
            'id' => $this->id,
            'user' => (new UserPresenter($this->user))->short(),
            'message' => $this->message,
            'file' => $this->file_url ? url($this->file_url) : null,
            'is_readed' => (boolean) $this->is_readed,
            'created_at' => $this->created_at,
        ];
    }
}