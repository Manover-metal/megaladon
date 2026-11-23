<?php

namespace App\Services\v1;

use App\Models\User;
use App\Repositories\PhoneConfirmationRepo;
use App\Services\BaseService;

class PhoneConfirmationService extends BaseService
{
    private PhoneConfirmationRepo $pcRepo;
    private $smsConfig;

    public function __construct() {
        $this->pcRepo = new PhoneConfirmationRepo();
        $this->smsConfig = config('smsc');
    }

    /**
     * Генерирует код, сохраняет его и пробует отправить SMS.
     *
     * @return string|null Код наружу — только в отладочном режиме (NO_SEND_SMS,
     *                     код там постоянный). При сбое шлюза — null: раньше код
     *                     уходил в ответ API, и чужой аккаунт можно было забрать,
     *                     зная только номер (сброс пароля в момент сбоя SMSC
     *                     или после выжигания баланса). Сбой пишет в лог SmscService.
     */
    public function sendCode(User $user, $phone)
    {
        $code = $this->generateCode();
        $this->pcRepo->store($user, $phone, $code);

        (new SmscService())->send($phone, __('sms.confirmation_code', ['code' => $code]));

        return $this->smsConfig['no_send_sms'] ? $code : null;
    }

    private function generateCode(): string
    {
        if ($this->smsConfig['no_send_sms']) {
            return (string) $this->smsConfig['debug_code'];
        }

        $length = min(max((int) $this->smsConfig['code_length'], 4), 8);

        // Нижняя граница — с единицы в старшем разряде: код всегда ровно $length
        // цифр, без ведущих нулей (иначе PHP сравнил бы '012345' и '12345' как
        // равные числовые строки при проверке).
        return (string) random_int(10 ** ($length - 1), (10 ** $length) - 1);
    }
}
