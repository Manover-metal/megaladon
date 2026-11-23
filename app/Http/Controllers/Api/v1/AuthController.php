<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\ConfirmCodeRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendCodeRequest;
use App\Http\Requests\Auth\SendCodeRequest;
use App\Http\Requests\Auth\ExecutorRegisterRequest;
use App\Http\Requests\Auth\PusherLoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\StoreRegistrationRequest;
use App\Services\v1\AuthService;
use App\Services\v1\PhoneConfirmationService;

class AuthController extends ApiController
{
    private AuthService $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        $response = $this->authService->login($credentials);
        return $this->result($response);
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $response = $this->authService->register($data);
        return $this->result($response);
    }

    public function registerExecutor(ExecutorRegisterRequest $request)
    {
        $response = $this->authService->registerExecutor($request->validated());
        return $this->result($response);
    }

    public function registerStore(StoreRegistrationRequest $request)
    {
        $response = $this->authService->registerStore($request->validated());
        return $this->result($response);
    }

    public function confirmCode(ConfirmCodeRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->authService->confirmCode($data));
    }

    public function sendCode(SendCodeRequest $request)
    {
        $user = $this->authUser();
        $code = (new PhoneConfirmationService())->sendCode($user, $user->phone);
        return $this->result(['verification_code' => $code]);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        return $this->result($this->authService->forgotPassword($request->validated()));
    }

    public function resendCode(ResendCodeRequest $request)
    {
        return $this->result($this->authService->resendCode($request->validated()));
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        return $this->result($this->authService->resetPassword($request->validated()));
    }

    public function logout()
    {
        return $this->result($this->authService->logout());
    }

    public function refresh(\Illuminate\Http\Request $request)
    {
        $data = $request->validate(['refresh_token' => ['required', 'string']]);
        return $this->result($this->authService->refresh($data['refresh_token']));
    }

    public function upgradeToken()
    {
        return $this->result($this->authService->upgradeToken());
    }

    public function pusherLogin(PusherLoginRequest $request)
    {
        return response()->json(
            $this->authService->pusherLogin($request->validated())
        );
    }
}
