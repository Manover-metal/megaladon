<?php

namespace App\Services\v1;

use App\Models\Report;
use App\Models\User;
use App\Models\UserBlock;
use App\Presenters\v1\UserPresenter;
use App\Services\BaseService;

class SafetyService extends BaseService
{
    public function block(User $user, int $blockedId)
    {
        if ($user->id === $blockedId) {
            return $this->errNotAcceptable(__('safety.cannot_block_self'));
        }
        if (!User::withTrashed()->whereKey($blockedId)->exists()) {
            return $this->errNotFound(__('user.not_found'));
        }

        // Повторная блокировка — не ошибка.
        UserBlock::firstOrCreate(['user_id' => $user->id, 'blocked_user_id' => $blockedId]);

        return $this->ok(__('safety.blocked'));
    }

    public function unblock(User $user, int $blockedId)
    {
        UserBlock::where('user_id', $user->id)->where('blocked_user_id', $blockedId)->delete();

        return $this->ok(__('safety.unblocked'));
    }

    public function blocked(User $user)
    {
        $users = User::withTrashed()
            ->whereIn('id', $user->blocks()->latest()->pluck('blocked_user_id'))
            ->get();

        return $this->resultCollections($users, UserPresenter::class, 'short');
    }

    public function report(User $reporter, array $data)
    {
        if ($reporter->id === (int) $data['user_id']) {
            return $this->errNotAcceptable(__('safety.cannot_report_self'));
        }

        $data['reporter_id'] = $reporter->id;
        $report = Report::create($data);

        return $this->result(['id' => $report->id, 'message' => __('safety.reported')]);
    }
}
