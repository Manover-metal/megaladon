<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    const STATUS_OPEN = 'open';
    const STATUS_RESOLVED = 'resolved';

    const RESOLUTION_DISMISSED = 'dismissed';
    const RESOLUTION_BANNED = 'banned';

    const TARGETS = ['user', 'order', 'advert', 'message', 'store'];
    const REASONS = ['spam', 'abuse', 'inappropriate', 'fake', 'other'];

    protected $fillable = ['reporter_id', 'user_id', 'target_type', 'target_id', 'reason', 'comment'];

    protected $casts = ['resolved_at' => 'datetime'];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id')->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function resolve(string $resolution, int $adminId): void
    {
        $this->forceFill([
            'status' => self::STATUS_RESOLVED,
            'resolution' => $resolution,
            'resolved_by' => $adminId,
            'resolved_at' => now(),
        ])->save();
    }

    public function getReasonName(): string
    {
        return [
            'spam' => 'Спам',
            'abuse' => 'Оскорбления',
            'inappropriate' => 'Неприемлемый контент',
            'fake' => 'Мошенничество / фейк',
            'other' => 'Другое',
        ][$this->reason] ?? $this->reason;
    }

    public function getStatusName(): string
    {
        if ($this->status === self::STATUS_OPEN) {
            return 'Открыта';
        }
        return $this->resolution === self::RESOLUTION_BANNED ? 'Забанен' : 'Отклонена';
    }
}
