<?php

namespace App\Services\v1;

use App\Models\User;
use App\Presenters\v1\RatingPresenter;
use App\Presenters\v1\UserPresenter;
use App\Repositories\ExecutorRepo;
use App\Repositories\PhoneConfirmationRepo;
use App\Repositories\UserRepo;
use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserService extends BaseService
{
    private UserRepo $userRepo;

    public function __construct() {
        $this->userRepo = new UserRepo();
    }

    public function updatePhoto(User $user, $data)
    {
        $path = $data['photo']->store('public/users');
        $this->userRepo->update($user->id, ['photo_url' => Storage::url($path)]);
        return $this->ok();
    }

    public function updateProfile(User $user, $data)
    {
        $updatedUser = $this->userRepo->update($user->id, $data);
        return $this->result(['user' => (new UserPresenter($updatedUser))->profile()]);
    }
    
    public function currentProfile(User $user)
    {
        return $this->result(['user' => (new UserPresenter($user))->profile()]);
    }

    public function profile(int $userId)
    {
        $user = User::find($userId);
        if (is_null($user)) {
            return $this->errNotFound(__('user.not_found'));
        }
        return $this->result(['user' => (new UserPresenter($user))->profile()]);
    }

    public function publicProfile(int $userId)
    {
        // User::find() идёт через глобальный scope SoftDeletes, поэтому
        // удалённый аккаунт не находится и сам отдаётся как 404 —
        // отдельная проверка trashed() не нужна.
        $user = User::find($userId);
        if (is_null($user)) {
            return $this->errNotFound(__('user.not_found'));
        }

        return $this->result(['user' => (new UserPresenter($user))->publicProfile()]);
    }

    /**
     * Отзывы пользователя для публичных экранов. Rating привязан
     * полиморфно к Executor, а не к User, поэтому сначала резолвим
     * профиль исполнителя. Ключуем по user_id: на экране отклика
     * известен именно он (OfferPresenter отдаёт UserPresenter->short()).
     */
    public function ratings(int $userId)
    {
        $user = User::find($userId);
        if (is_null($user)) {
            return $this->errNotFound(__('user.not_found'));
        }

        $executor = (new ExecutorRepo())->findByUserId($user->id);
        // Профиля исполнителя нет — это не ошибка: у человека просто
        // не может быть отзывов, отдаём пустой список.
        if (is_null($executor)) {
            return $this->resultCollections([], RatingPresenter::class, 'list');
        }

        $ratings = $executor->ratings()->with(['media', 'user'])->latest()->get();

        return $this->resultCollections($ratings, RatingPresenter::class, 'list');
    }

    public function startChangePhone($data)
    {
        $user = $this->apiAuthUser();
        if (!Hash::check($data['password'], $user->password)) {
            return $this->error(401, __('user.wrong_password'));
        }
        (new PhoneConfirmationService())->sendCode($user, $data['new_phone']);

        return $this->ok();
    }

    public function changePassword(User $user, array $data)
    {
        if (!Hash::check($data['old_password'], $user->password)) {
            return $this->error(401, __('user.wrong_password'));
        }

        $this->userRepo->update($user->id, ['password' => Hash::make($data['password'])]);

        return $this->ok(__('user.password_changed'));
    }

    public function deleteAccount(User $user, array $data)
    {
        if (!Hash::check($data['password'], $user->password)) {
            return $this->error(401, __('user.wrong_password'));
        }

        $user->tokens()->delete();
        $user->delete();

        return $this->ok(__('user.account_deleted'));
    }

    public function endChangePhone($data)
    {
        $pcRepo = new PhoneConfirmationRepo();
        $user = $this->apiAuthUser();
        $phoneConfirmation = $pcRepo->getByUserIdAndPhone($user->id, $data['phone']);
        
        if (is_null($phoneConfirmation)) {
            return $this->errNotFound(__('user.confirmation_code_not_found'));
        }

        if ($phoneConfirmation->code != $data['code']) {
            return $this->error(406, __('user.wrong_confirmation_code'));
        }

        $this->userRepo->update($user->id, ['phone' => $data['phone']]);
        // Код одноразовый — иначе старым кодом можно привязать номер повторно.
        $pcRepo->deleteByPhone($data['phone']);

        return $this->ok(__('user.phone_changed'));
    }

    public function updateToken(array $data) : array
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated(__('user.unauthorized'));
        }
        $data = [
            'device_token' => $data['token'],
            'push_notifications' => 1,
        ];
        $this->userRepo->update($user->id, $data);

        return $this->ok();
    }

    public function disableNotifications()
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated(__('user.unauthorized'));
        }
        $data = [
            'device_token' => '',
            'push_notifications' => 0,
        ];
        $this->userRepo->update($user->id, $data);

        return $this->ok();
    }

    public function pushStatus(): array
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated(__('user.unauthorized'));
        }

        return $this->result([
            'push_notifications' => (bool) $user->push_notifications,
            'device_token' => $user->device_token,
        ]);
    }

    public function changePushStatus(array $data): array
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated(__('user.unauthorized'));
        }

        // push_notifications — это сам флаг «пуши вкл/выкл». filter_var, т.к.
        // UserRepo::update идёт через query-builder и касты модели не
        // применяются ((bool)"0" === true).
        $pushEnabled = filter_var($data['push_notifications'], FILTER_VALIDATE_BOOLEAN);

        // Включить пуши без device_token нельзя: токен приходит только когда
        // уведомления разрешены в ОС. Нет токена — некуда слать пуш, поэтому
        // отдаём понятную локализованную ошибку, а флаг не трогаем.
        if ($pushEnabled && empty($data['device_token'])) {
            return $this->error(422, __('push.enable_failed'));
        }

        $updateData = [
            'push_notifications' => $pushEnabled,
        ];
        // device_token обновляем только если он реально передан.
        if (array_key_exists('device_token', $data)) {
            $updateData['device_token'] = $data['device_token'];
        }

        $this->userRepo->update($user->id, $updateData);

        return $this->result([
            'push_notifications' => $updateData['push_notifications'],
        ]);
    }
}