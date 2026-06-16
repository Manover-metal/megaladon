<?php

namespace App\Services\v1;

use App\Models\User;
use App\Presenters\v1\UserPresenter;
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
            return $this->errNotFound('Пользователь не найден');
        }
        return $this->result(['user' => (new UserPresenter($user))->profile()]);
    }

    public function startChangePhone($data)
    {
        $user = $this->apiAuthUser();
        if (!Hash::check($data['password'], $user->password)) {
            return $this->error(401, 'Введен неверный пароль');
        }
        (new PhoneConfirmationService())->sendCode($user, $data['new_phone']);

        return $this->ok();
    }

    public function changePassword(User $user, array $data)
    {
        if (!Hash::check($data['old_password'], $user->password)) {
            return $this->error(401, 'Введен неверный пароль');
        }

        $this->userRepo->update($user->id, ['password' => Hash::make($data['password'])]);

        return $this->ok('Пароль был успешно изменён');
    }

    public function endChangePhone($data)
    {
        $pcRepo = new PhoneConfirmationRepo();
        $user = $this->apiAuthUser();
        $phoneConfirmation = $pcRepo->getByUserIdAndPhone($user->id, $data['phone']);
        
        if (is_null($phoneConfirmation)) {
            return $this->errNotFound('Не удалось найти код подтверждения, попробуйте чуть позднее');
        }

        if ($phoneConfirmation->code != $data['code']) {
            return $this->error(406, 'Введен не верный код-подтверждения');
        }

        $this->userRepo->update($user->id, ['phone' => $data['phone']]);
        return $this->ok('Телефон изменён');
    }

    public function updateToken(array $data) : array
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->error(403, 'Пользователь не авторизован');
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
            return $this->error(403, 'Пользователь не авторизован');
        }
        $data = [
            'device_token' => '',
            'push_notifications' => 0,
        ];
        $this->userRepo->update($user->id, $data);

        return $this->ok();
    }
}