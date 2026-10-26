<?php

namespace App\Services\v1;

use App\Models\SupportTicket;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\Storage;

class SupportService extends BaseService
{
    public function create(User $user, array $data)
    {
        $attachments = [];
        foreach ($data['screenshots'] ?? [] as $file) {
            $attachments[] = Storage::url($file->store('public/support'));
        }

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'message' => $data['message'],
            'attachments' => $attachments,
        ]);

        return $this->result(['id' => $ticket->id, 'message' => __('support.sent')]);
    }

    public function my(User $user)
    {
        $tickets = SupportTicket::where('user_id', $user->id)->latest()->get();

        return $this->resultCollections($tickets);
    }
}
