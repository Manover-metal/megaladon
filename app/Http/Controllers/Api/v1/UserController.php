<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\ChangePhoneRequest;
use App\Http\Requests\User\ChangePushStatusRequest;
use App\Http\Requests\User\EndChangePhoneRequest;
use App\Http\Requests\User\GetProfileRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Requests\User\UpdateTokenRequest;
use App\Http\Requests\User\UpdateUserPhotoRequest;
use App\Services\v1\UserService;
use Illuminate\Support\Facades\Request;

class UserController extends ApiController
{
    private UserService $userService;

    public function __construct() {
        $this->userService = new UserService();
    }

    public function updatePhoto(UpdateUserPhotoRequest $request)
    {
        $data = $request->validated();
        $response = $this->userService->updatePhoto($this->authUser(), $data);
        return $this->result($response);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $data = $request->validated();
        $response = $this->userService->updateProfile($this->authUser(), $data);
        return $this->result($response);
    }

    public function profile(GetProfileRequest $request, $id)
    {
        $response = $this->userService->profile($id);
        return $this->result($response);
    }

    public function currentProfile(GetProfileRequest $request)
    {
        $response = $this->userService->currentProfile($this->authUser());
        return $this->result($response);
    }

    public function startChangePhone(ChangePhoneRequest $request)
    {
        $data = $request->validated();
        $response = $this->userService->startChangePhone($data);
        return $this->result($response);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->userService->changePassword($this->authUser(), $data));
    }

    public function endChangePhone(EndChangePhoneRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->userService->endChangePhone($data));
    }

    public function updateToken(UpdateTokenRequest $request)
    {
        return $this->result($this->userService->updateToken($request->validated()));
    }

    public function disableNotifications()
    {
        return $this->result($this->userService->disableNotifications());
    }

    public function pushStatus()
    {
        return $this->result($this->userService->pushStatus());
    }

    public function changePushStatus(ChangePushStatusRequest $request)
    {
        return $this->result($this->userService->changePushStatus($request->validated()));
    }
}