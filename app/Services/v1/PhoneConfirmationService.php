<?php

namespace App\Services\v1;

use App\Models\User;
use App\Repositories\PhoneConfirmationRepo;
use App\Services\BaseService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PhoneConfirmationService extends BaseService
{
    private PhoneConfirmationRepo $pcRepo;
    private $smsConfig;

    public function __construct() {
        $this->pcRepo = new PhoneConfirmationRepo();
        $this->smsConfig = config('smsc');
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