# Auth Bug Fixes — Сводка исправлений

Дата: 2026-05-27

---

## Flutter (megaladon_app)

### Баг 1 — Форм-ошибки никогда не показывались пользователю
**Файлы:** `lib/presentation/screens/auth/login_screen.dart`, `lib/presentation/screens/auth/register/register_user_screen.dart`

**Проблема:**
Метод `_listenerForm` проверял `if(state.status)`, где `status = true` означает что форма **валидна**. В итоге условие срабатывало только когда всё правильно, а ошибки внутри были недостижимы. При невалидных полях `status = false` — блок никогда не выполнялся и снекбар не показывался.

**Исправление:**
```dart
// Было:
if(state.status) { ... }

// Стало:
if (!state.status) { ... }
```

---

### Баг 2 — Повторный тап с теми же данными не показывал ошибку
**Файл:** `lib/logic/form/auth/auth_form_state.dart`

**Проблема:**
`countTry` не был включён в `props` класса `AuthFormState`. Equatable считал состояние одинаковым при повторном тапе с теми же невалидными данными → `BlocListener` не срабатывал повторно → пользователь не видел ошибку второй раз.

**Исправление:**
```dart
// Было:
List<Object?> get props => [status, phone, password];

// Стало:
List<Object?> get props => [status, phone, password, countTry];
```

---

### Баг 3 — Серверные ошибки валидации не парсились
**Файл:** `lib/data/models/error_model.dart`

**Проблема 1:** При ошибке Laravel возвращает `errors` как `Map<String, List>`:
```json
{ "errors": { "phone": ["Такой номер уже существует"], "password": ["Слишком короткий"] } }
```
Код `data['errors'].map((val) => val)` в Dart вызывает `Map.map()`, который ожидает функцию `(key, value) → MapEntry` — это выбрасывало исключение и возвращался `ErrorModel.nothing`.

**Проблема 2:** `message` проверялось раньше `errors`, поэтому при наличии обоих полей всегда показывалось только первое общее сообщение вместо конкретных ошибок полей.

**Исправление:**
```dart
// Сначала проверяем errors (конкретные ошибки полей), потом message (общее)
if (data?['errors'] is List) {
  return ErrorModel(List<String>.from(data['errors']));
} else if (data?['errors'] is Map) {
  final messages = <String>[];
  (data['errors'] as Map).forEach((key, value) {
    if (value is List) messages.addAll(value.cast<String>());
    else if (value is String) messages.add(value);
  });
  if (messages.isNotEmpty) return ErrorModel(messages);
}
if (data?['message'] != null) {
  return ErrorModel([data['message'] as String]);
}
```

---

### Баг 4 — resetPassword вызывал неправильный endpoint
**Файл:** `lib/data/repositories/auth/auth_repository.dart`

**Проблема:**
Метод `resetPassword` использовал `DELETE /auth/logout` — то есть вызывал выход из аккаунта вместо сброса пароля.

**Исправление:**
```dart
// Было:
return ApiService.I.delete('/auth/logout', data: {'phone': phone});

// Стало:
return ApiService.I.post('/auth/reset-password', data: {'phone': phone});
```

---

## Laravel Backend (megaladon)

### Баг 5 — Можно зарегистрировать два аккаунта с одним номером
**Файл:** `app/Http/Requests/Auth/RegisterRequest.php`

**Проблема:**
Не было правила `unique:users,phone` в валидации. Проверка на дубликат была только на уровне услугаа (`getUserByPhone`), без защиты на уровне базы данных. При параллельных запросах оба могли пройти проверку.

**Исправление:**
```php
// Было:
'phone' => ['required', 'string', 'starts_with:+'],

// Стало:
'phone' => ['required', 'string', 'starts_with:+', 'unique:users,phone'],
```

---

### Баг 6 — Несоответствие минимальной длины пароля
**Файл:** `app/Http/Requests/Auth/RegisterRequest.php`

**Проблема:**
Бэкенд принимал пароли от 6 символов (`min:6`), а Flutter валидировал от 8 символов (`value.length <= 7`). Пользователь мог зарегистрироваться через API с паролем из 6 символов, но не смог бы сделать это через приложение — или наоборот.

**Исправление:**
```php
// Было:
'password' => ['required', 'string', 'min:6', 'max:32', 'confirmed'],

// Стало:
'password' => ['required', 'string', 'min:8', 'max:32', 'confirmed'],
```

---

### Баг 7 — Неправильный текст ошибки в resetPassword
**Файл:** `app/Services/v1/AuthService.php`

**Проблема:**
Сообщение об ошибке при сбросе пароля гласило "Пользователь с таким **паролем** не существует" — явная опечатка. Пользователь вводит номер телефона, а не пароль.

**Исправление:**
```php
// Было:
return $this->errNotFound('Пользователь с таким паролем не существует');

// Стало:
return $this->errNotFound('Пользователь с таким номером не найден');
```

---

### Баг 8 — Код подтверждения был захардкожен
**Файл:** `app/Services/v1/PhoneConfirmationService.php`

**Проблема:**
Код подтверждения был зафиксирован как `101010` вместо случайного числа. Любой пользователь знал код заранее и мог подтвердить чужой номер.

**Исправление:**
```php
// Было:
$code = 101010; //rand(100000, 999999);

// Стало:
$code = rand(100000, 999999);
```

---

## SMS-услуга (требует подключения)

Отправка SMS в `PhoneConfirmationService::sendCode()` не реализована (стоял TODO).
Код теперь генерируется случайно, но SMS по-прежнему не отправляется до подключения провайдера.

**Рекомендуемые провайдеры для KZ/RU:**
- **Mobizon** (https://mobizon.kz) — популярен в Казахстане
- **SMSC.ru** (https://smsc.ru) — популярен в России
- **Twilio** (https://twilio.com) — международный

После выбора провайдера нужно реализовать метод отправки в `PhoneConfirmationService.php` и добавить API-ключи в `.env`.

---

---

### Улучшение 9 — Скрытие пароля с кнопкой-глазком
**Файлы:** `lib/presentation/widgets/form/field/password_field.dart` (создан), `login_screen.dart`, `register_user_screen.dart`, `change_password_screen.dart`

**Проблема:**
Поля пароля использовали обычный `TextFieldApp` без `obscureText` — пароль был виден при вводе.

**Исправление:**
Создан отдельный виджет `PasswordFieldApp` (`StatefulWidget`) с `obscureText: true` по умолчанию и `IconButton` с иконкой `visibility`/`visibility_off` для переключения. Подключён во все экраны с полями пароля: логин, регистрация, смена пароля.

---

### Улучшение 10 — Города в базе данных
**Файл:** `database/seeders/CitySeeder.php`

**Проблема:**
Таблица `cities` была пустой — `CityPicker` при нажатии делал `if (cities.isEmpty) return` и ничего не открывал.

**Исправление:**
Запущен сидер с 20 городами Казахстана:
```bash
php artisan db:seed --class=CitySeeder
```

---

## Итоговая таблица

| # | Проект | Файл | Тип | Статус |
|---|--------|------|-----|--------|
| 1 | Flutter | `login_screen.dart`, `register_user_screen.dart` | Логика отображения ошибок | ✅ |
| 2 | Flutter | `auth_form_state.dart` | Equatable props (countTry) | ✅ |
| 3 | Flutter | `error_model.dart` | Парсинг серверных ошибок | ✅ |
| 4 | Flutter | `auth_repository.dart` | Неправильный endpoint resetPassword | ✅ |
| 5 | Laravel | `RegisterRequest.php` | Нет unique phone | ✅ |
| 6 | Laravel | `RegisterRequest.php` | Несоответствие min пароля (6 vs 8) | ✅ |
| 7 | Laravel | `AuthService.php` | Опечатка в сообщении ошибки | ✅ |
| 8 | Laravel | `PhoneConfirmationService.php` | Захардкоженный код подтверждения | ✅ |
| 9 | Flutter | `password_field.dart` + экраны | Пароль не скрывался, нет глазка | ✅ |
| 10 | Laravel | `CitySeeder` | База городов была пустая | ✅ |
| 11 | Flutter | `drawer_app.dart` | Logout не очищал стек навигации | ✅ |
| 12 | Flutter | `verify_screen.dart`, `login_screen.dart` | `navigate()` оставлял старые роуты в стеке | ✅ |
| 13 | Laravel | `RegisterRequest.php` | Ошибки валидации на английском | ✅ |
| — | Laravel | `PhoneConfirmationService.php` | SMS не отправляется | ⏳ SMS-провайдер |
| — | Flutter + Laravel | `forgot_password_screen.dart`, `AuthBloc`, `AuthService` | Флоу сброса пароля не реализован | ⏳ TODO |

---

## TODO — Флоу "Забыли пароль?" (не реализован)

### Текущее состояние

**Бэкенд (`POST /auth/reset-password`):**
- ✅ Роут зарегистрирован
- ✅ `AuthService::resetPassword()` — находит пользователя, генерирует случайный пароль (`Str::random(10)`), сохраняет в БД
- ❌ SMS не отправляется (TODO-комментарий в коде)

**Фронтенд:**
- ❌ Кнопка "Забыли пароль?" в `login_screen.dart` — это просто `Text` без `onTap`, никуда не ведёт
- ❌ `ForgotPasswordScreen` — пустая заглушка, нет контроллера, нет BLoC
- ❌ `ResetPasswordScreen` — пустая заглушка
- ❌ `AuthBloc` не имеет события `AuthResetPasswordEvent`

### Что нужно сделать

1. **Подключить SMS-провайдер** (Mobizon / SMSC.ru / Twilio) — без этого пользователь не получит новый пароль
2. **Flutter**: добавить `AuthResetPasswordEvent` и обработчик в `AuthBloc`
3. **Flutter**: реализовать `ForgotPasswordScreen` — поле телефона, вызов bloc-события, лоадер, обработка ошибок
4. **Flutter**: добавить `GestureDetector` на "Забыли пароль?" в `login_screen.dart` для навигации на `ForgotPasswordRoute`

---

### Баг 11 — После логаута открывался экран верификации
**Файл:** `lib/presentation/widgets/drawer/drawer_app.dart`

**Проблема:**
`_logout` только отправлял `AuthLogoutEvent` в блок, но не выполнял навигацию. После logout `AuthInitial` эмитился, но роутер оставался на текущем экране. При этом `VerifyRoute` оставался в стеке (см. Баг 12) и при следующем действии всплывал.

**Исправление:**
```dart
// Было:
_logout(BuildContext context) => () {
  context.read<AuthBloc>().add(AuthLogoutEvent());
};

// Стало:
_logout(BuildContext context) => () {
  context.read<AuthBloc>().add(AuthLogoutEvent());
  context.router.replaceAll([const LoginRoute()]);
};
```

---

### Баг 12 — `navigate()` оставлял старые роуты в стеке
**Файлы:** `lib/presentation/screens/auth/verify_screen.dart`, `lib/presentation/screens/auth/login_screen.dart`

**Проблема:**
После успешного входа/верификации использовался `context.router.navigate(InitialRouter(...))`. В `auto_route` метод `navigate` добавляет роут поверх стека, не очищая его — `VerifyRoute` и `LoginRoute` оставались под `InitialRouter`. После логаута роутер находил их и показывал.

**Исправление:**
```dart
// Было:
context.router.navigate(const InitialRouter(children: [ProfileRouter()]));

// Стало:
context.router.replaceAll([const InitialRouter(children: [ProfileRouter()])]);
```

---

### Баг 13 — Ошибки валидации регистрации на английском
**Файл:** `app/Http/Requests/Auth/RegisterRequest.php`

**Проблема:**
Laravel по умолчанию возвращает ошибки валидации на английском (например: "The phone has already been taken"). Метод `messages()` не был переопределён.

**Исправление:**
Добавлен метод `messages()` с русскими текстами для всех правил:
```php
public function messages()
{
    return [
        'phone.unique'       => 'Пользователь с таким номером уже зарегистрирован',
        'password.confirmed' => 'Пароли не совпадают',
        'password.min'       => 'Пароль должен содержать не менее 8 символов',
        'city_id.required'   => 'Выберите город',
        // ...
    ];
}
```
