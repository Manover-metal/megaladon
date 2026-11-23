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
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

class AuthService extends BaseService
{
    private UserRepo $userRepo;
    private $smsConfig;

    public function __construct() {
        $this->userRepo = new UserRepo();
        $this->smsConfig = config('smsc');
    }

    public function login(array $data) : array
    {
        $user = $this->userRepo->getUserByPhone($data['phone']);
        if (is_null($user)) {
            return $this->errNotFound(__('account.invalid_credentials'));
        }

        if ($user->is_phone_confirmed == 0) {
            return $this->error(406, __('account.confirm_phone_first'));
        }
        
        if (!Hash::check($data['password'], $user->password)) {
            return $this->error(401, __('account.invalid_credentials'));
        }

        if ($user->isBanned()) {
            return $this->errFobidden(__('account.banned', ['reason' => $user->ban_reason ?: '—']));
        }

        return $this->result((new TokenService())->issue($user) + [
            'user' => (new UserPresenter($user))->profile(),
        ]);
    }

    public function register(array $data) : array
    {
        $user = $this->userRepo->getUserByPhone($data['phone']);

        if ($user?->is_phone_confirmed) {
            return $this->errValidate(__('account.phone_exists'));
        }

        // Номер есть, но SMS не подтверждён — человек бросил регистрацию на
        // шаге кода. Чужой аккаунт так не захватить: владельцем станет тот,
        // кто введёт код из SMS. Пароль хэширует мутатор User.
        if ($user) {
            $user->fill($data)->save();
        } else {
            $user = $this->userRepo->store($data);
        }
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
            return $this->errUnauthenticated('Auth error');
        }

        $executorRepo = new ExecutorRepo();
        $executor = $executorRepo->findByUserId($user->id);
        if (!is_null($executor)) {
            return $this->error(406, __('account.already_executor'));
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
            return $this->errUnauthenticated('Unauthorized');
        }

        $storeRepo = new StoreRepo();
        if (!is_null($storeRepo->getByUserId($user->id))) {
            return $this->error(406, __('account.already_has_store'));
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
        // Именно последний выданный код. Раньше здесь был getByPhone() —
        // ->first() без сортировки, то есть строка с наименьшим id: на номер
        // проверялся самый первый код за всю его историю. Коды не удалялись,
        // и номер, получивший 101010 в период NO_SEND_SMS=true, навсегда
        // подтверждался этим кодом.
        $code = (new PhoneConfirmationRepo())->getLatestByPhone($data['phone']);

        if (!$code) {
            return $this->errNotFound(__('account.phone_invalid'));
        }

        if ($code->code != $data['code']) {
            return $this->error(400, __('account.code_invalid'));
        }

        $this->userRepo->confirmPhone($data['phone']);
        // Код одноразовый — гасим все выданные на номер, как в resetPassword().
        (new PhoneConfirmationRepo())->deleteByPhone($data['phone']);
        $user = $this->userRepo->getUserByPhone($data['phone']);
        return $this->result((new TokenService())->issue($user) + [
            'user' => (new UserPresenter($user))->profile(),
        ]);
    }

    public function forgotPassword(array $data)
    {
        $user = $this->userRepo->getUserByPhone($data['phone']);
        if (is_null($user)) {
            return $this->errNotFound(__('account.user_not_found'));
        }

        $code = (new PhoneConfirmationService())->sendCode($user, $data['phone']);

        return $this->result([
            'verification_code' => $code,
        ]);
    }

    public function resendCode(array $data)
    {
        $user = $this->userRepo->getUserByPhone($data['phone']);
        if (is_null($user)) {
            return $this->errNotFound(__('account.user_not_found'));
        }

        $code = (new PhoneConfirmationService())->sendCode($user, $data['phone']);

        return $this->result([
            'verification_code' => $code,
        ]);
    }

    public function resetPassword(array $data)
    {
        $confirmation = (new PhoneConfirmationRepo())->getLatestByPhone($data['phone']);
        if (is_null($confirmation)) {
            return $this->errNotFound(__('account.phone_invalid'));
        }

        if ($confirmation->code != $data['code']) {
            return $this->error(400, __('account.code_invalid'));
        }

        $user = $this->userRepo->getUserByPhone($data['phone']);
        if (is_null($user)) {
            return $this->errNotFound(__('account.user_not_found'));
        }

        $this->userRepo->update($user->id, ['password' => Hash::make($data['password'])]);
        (new PhoneConfirmationRepo())->deleteByPhone($data['phone']);

        return $this->ok(__('account.password_reset_success'));
    }

    public function logout()
    {
        $user = auth('api')->user();
        if (is_null($user)) {
            return $this->errUnauthenticated('Unauthorized');
        }
        // Только токен этого устройства (и его refresh): tokens()->delete()
        // разлогинивал пользователя заодно на всех остальных телефонах.
        $token = $user->currentAccessToken();
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            (new TokenService())->revoke($token);
        }
        return $this->ok();
    }

    /** Ротация пары. 401 — refresh не принят, приложению пора на вход. */
    public function refresh(string $refreshToken): array
    {
        $pair = (new TokenService())->refresh($refreshToken);
        if (is_null($pair)) {
            return $this->errUnauthenticated(__('account.refresh_invalid'));
        }
        return $this->result($pair);
    }

    /**
     * Обмен вечного токена старой версии приложения на пару. Старый токен
     * гасится. Админский токен так не обменять — у админки refresh нет.
     */
    public function upgradeToken(): array
    {
        $user = auth('api')->user();
        $token = $user?->currentAccessToken();
        if (!$token instanceof \Laravel\Sanctum\PersonalAccessToken || $token->name !== 'api') {
            return $this->errUnauthenticated('Unauthorized');
        }
        $tokens = new TokenService();
        $tokens->revoke($token);
        return $this->result($tokens->issue($user));
    }

    public function pusherLogin(array $data)
    {
        $user = auth('api')->user();

        $userData = json_encode([
            'user_id' => auth('api')->user()->id,
            'user_info' => (new UserPresenter($user))->short(),
        ]);

        $pusher = new Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id')
        );
        $auth = $pusher->authorizeChannel($data['channel_name'], $data['socket_id'], $userData);

        return [
                'auth' => json_decode($auth)->auth,
                'channel_data' => $userData,
            ];
    }
}