<?php

namespace App\Services\v1;

use App\Models\User;
use App\Presenters\v1\ExecutorPresenter;
use App\Presenters\v1\StorePresenter;
use App\Presenters\v1\UserPresenter;
use App\Repositories\ExecutorRepo;
use App\Repositories\PhoneConfirmationRepo;
use App\Repositories\StoreRepo;
use App\Services\BaseService;
use App\Repositories\UserRepo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Str;

class AuthService extends BaseService
{
    private UserRepo $userRepo;

    public function __construct() {
        $this->userRepo = new UserRepo();
    }

    public function login(array $data) : array
    {
        $user = $this->userRepo->getUserByPhone($data['phone']);
        if (is_null($user)) {
            return $this->errNotFound('Неверные номер пользователя или пароль');
        }

        if ($user->is_phone_confirmed == 0) {
            return $this->error(406, 'Сначала подтвердите номер телефона');
        }
        
        if (!Hash::check($data['password'], $user->password)) {
            return $this->error(401, 'Неверные номер пользователя или пароль');
        }

        $token = $user->createToken('api')->plainTextToken;

        return $this->result([
            'token' => $token,
            'user' => (new UserPresenter($user))->profile(),
        ]);
    }

    public function register(array $data) : array
    {
        if ($this->userRepo->getUserByPhone($data['phone'])) {
            return $this->errValidate('Пользователь с таким номером существует');
        }

        $data['password'] = Hash::make($data['password']);

        $user = $this->userRepo->store($data);
        $code = (new PhoneConfirmationService())->sendCode($user, $data['phone']);

        return $this->result([
            'user' => $user,
            'verification_code' => $code,
        ]);
    }

    public function registerExecutor(array $data)
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->error(403, 'Auth error');
        }

        $executorRepo = new ExecutorRepo();
        $executor = $executorRepo->findByUserId($user->id);
        if (!is_null($executor)) {
            return $this->error(406, 'Вы уже зарегистированны как исполнитель');
        }

        $data['user_id'] = $user->id;

        $executor = $executorRepo->store($data);
        $executor->services()->sync($data['services']);

        $executor = $executorRepo->info($user->id);

        return $this->result([
            'user' => $user,
            'executor' => (new ExecutorPresenter($executor))->edited(),
        ]);
    }

    public function registerStore(array $data)
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->error(403, 'Unauthorized');
        }

        $storeRepo = new StoreRepo();
        if (!is_null($storeRepo->getByUserId($user->id))) {
            return $this->error(406, 'У вас уже есть зарегистрированный магазин');
        }

        $data['user_id'] = $user->id;

        $store = (new StoreRepo())->store($data);

        foreach ($data['contacts'] as $contactData) {
            $contactData['store_id'] = $store->id;
            $store->contacts()->create($contactData);
        }

        return $this->result([
            'user' => $user,
            'store' => $store,
        ]);
    }

    public function confirmCode(array $data) : array
    {
        $code = (new PhoneConfirmationRepo())->getByPhone($data['phone']);

        if (!$code) {
            return $this->errNotFound('Номер телефона не корректен');
        }

        if ($code->code != $data['code']) {
            return $this->error(400, 'Код подтверждения указан неверно');
        }

        $this->userRepo->confirmPhone($data['phone']);
        $user = $this->userRepo->getUserByPhone($data['phone']);
        $token = $user->createToken('api')->plainTextToken;

        return $this->result([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function resetPassword(array $data)
    {
        $user = $this->userRepo->getUserByPhone($data['phone']);
        if (is_null($user)) {
            return $this->errNotFound('Пользователь с таким паролем не существует');
        }

        $newPassword = Str::random(10);
        //TODO: Сделать отправку по смс
        $this->userRepo->update($user->id, ['password' => Hash::make($newPassword)]);

        return $this->ok('Смс с паролем было высланно на указанный номер');
    }

    public function logout()
    {
        $user = auth('api')->user();
        if (is_null($user)) {
            return $this->errFobidden('Unauthorized');
        }
        $user->tokens()->delete();
        return $this->ok();
    }
}