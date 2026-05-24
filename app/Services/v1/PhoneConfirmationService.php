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
        $code = 101010;//rand(100000, 999999);
        $this->pcRepo->store($user, $phone, $code);

        //TODO:Сделать отправку смс
        return $code;
    }
}