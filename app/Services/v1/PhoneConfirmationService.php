<?php

namespace App\Services\v1;

use App\Models\User;
use App\Repositories\PhoneConfirmationRepo;
use App\Services\BaseService;

class PhoneConfirmationService extends BaseService
{
    private PhoneConfirmationRepo $pcRepo;

    public function __construct() {
        $this->pcRepo = new PhoneConfirmationRepo();
    }

    public function sendCode(User $user, $phone)
    {
        // TODO: вернуть случайную генерацию после подключения SMS-провайдера
        $code = 101010;
        $this->pcRepo->store($user, $phone, $code);

        // TODO: Подключить SMS-провайдер (Mobizon / SMSC.ru / Twilio)
        // Пример для Mobizon: https://mobizon.kz/help/api-docs/sms-api
        // $this->sendSms($phone, "Ваш код подтверждения: {$code}");

        return $code;
    }
}