<?php

namespace App\Repositories;

use App\Models\PhoneConfirmation;
use App\Models\User;

class PhoneConfirmationRepo
{
    public function store(User $user, $phone, $code)
    {
        return PhoneConfirmation::create([
            'user_id' => $user->id,
            'phone' => $phone,
            'code' => $code,
        ]);
    }

    public function getLatestByPhone($phone)
    {
        return PhoneConfirmation::where('phone', $phone)
            ->latest('id')
            ->first();
    }

    public function getByUserIdAndPhone(int $userId, $phone)
    {
        return PhoneConfirmation::where('user_id', $userId)
            ->where('phone', $phone)
            ->latest('id')
            ->first();
    }

    public function deleteByPhone($phone)
    {
        return PhoneConfirmation::where('phone', $phone)->delete();
    }
}