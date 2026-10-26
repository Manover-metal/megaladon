<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    const STATUS_OPEN = 'open';
    const STATUS_ANSWERED = 'answered';
    const STATUS_CLOSED = 'closed';

    const STATUS_NAMES = [
        self::STATUS_OPEN => 'Открыто',
        self::STATUS_ANSWERED => 'Отвечено',
        self::STATUS_CLOSED => 'Закрыто',
    ];

    protected $fillable = ['user_id', 'message', 'attachments', 'status', 'admin_reply', 'replied_at'];

    protected $casts = ['attachments' => 'array', 'replied_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function getStatusName(): string
    {
        return self::STATUS_NAMES[$this->status] ?? $this->status;
    }
}
