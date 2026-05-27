<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'string', 'starts_with:+', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'max:32', 'confirmed'],
            'city_id' => ['required', 'exists:cities,id'],
        ];
    }

    public function messages()
    {
        return [
            'name.required'      => 'Имя обязательно для заполнения',
            'name.min'           => 'Имя должно содержать не менее 2 символов',
            'phone.required'     => 'Номер телефона обязателен',
            'phone.starts_with'  => 'Номер телефона должен начинаться с +',
            'phone.unique'       => 'Пользователь с таким номером уже зарегистрирован',
            'password.required'  => 'Пароль обязателен',
            'password.min'       => 'Пароль должен содержать не менее 8 символов',
            'password.confirmed' => 'Пароли не совпадают',
            'city_id.required'   => 'Выберите город',
            'city_id.exists'     => 'Выбранный город не найден',
        ];
    }
}
