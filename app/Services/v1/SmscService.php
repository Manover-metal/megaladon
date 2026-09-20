<?php

namespace App\Services\v1;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Отправка SMS через HTTP-API SMSC.kz (https://smsc.kz/api/http/).
 *
 * Сервис намеренно не бросает исключений: падение шлюза не должно ронять
 * регистрацию или смену телефона. Наружу отдаётся bool «ушло / не ушло»,
 * подробности — в лог.
 */
class SmscService
{
    private array $config;

    public function __construct()
    {
        $this->config = config('smsc');
    }

    /**
     * @return bool true — SMSC принял сообщение; false — отправка отключена,
     *              не настроена или шлюз вернул ошибку.
     */
    public function send(string $phone, string $message): bool
    {
        if ($this->config['no_send_sms']) {
            Log::info(__METHOD__ . ': NO_SEND_SMS=true, отправка пропущена, phone=' . $phone);
            return false;
        }

        $auth = $this->authParams();
        if ($auth === []) {
            Log::error(__METHOD__ . ': не заданы SMSC_API_KEY либо SMSC_LOGIN/SMSC_PASSWORD, SMS не отправлена');
            return false;
        }

        $phones = $this->normalizePhone($phone);
        if ($phones === '') {
            Log::error(__METHOD__ . ': пустой номер после нормализации, исходный=' . $phone);
            return false;
        }

        $params = $auth + [
            'phones' => $phones,
            'mes' => $message,
            'fmt' => 3, // ответ в JSON
            'charset' => $this->config['charset'],
            'translit' => $this->config['translit'],
        ];

        if (!empty($this->config['sender'])) {
            $params['sender'] = $this->config['sender'];
        }

        try {
            $response = (new Client(['timeout' => $this->config['timeout']]))
                ->post($this->config['url'] . 'send.php', ['form_params' => $params]);
        } catch (GuzzleException $e) {
            Log::error(__METHOD__ . ': запрос к SMSC не прошёл — ' . $e->getMessage());
            return false;
        }

        $body = json_decode((string) $response->getBody(), true);

        if (!is_array($body)) {
            Log::error(__METHOD__ . ': SMSC вернул нечитаемый ответ');
            return false;
        }

        if (isset($body['error'])) {
            Log::error(sprintf(
                '%s: SMSC отклонил отправку на %s — %s (code %s)',
                __METHOD__,
                $phones,
                $body['error'],
                $body['error_code'] ?? '-'
            ));
            return false;
        }

        if (!isset($body['id'])) {
            Log::error(__METHOD__ . ': в ответе SMSC нет id сообщения');
            return false;
        }

        Log::info(sprintf('%s: SMS на %s принята SMSC, id=%s', __METHOD__, $phones, $body['id']));
        return true;
    }

    /**
     * Авторизация: по докам apikey функционально заменяет пару логин+пароль,
     * поэтому он в приоритете. Пароль SMSC принимает открытым текстом; MD5 в
     * документации не описан, так что SMSC_PASSWORD_IS_MD5 отправку не ломает —
     * только предупреждает, если значение не похоже на хеш.
     */
    private function authParams(): array
    {
        if (!empty($this->config['api_key'])) {
            return ['apikey' => $this->config['api_key']];
        }

        if (empty($this->config['login']) || empty($this->config['password'])) {
            return [];
        }

        if ($this->config['password_is_md5'] && !preg_match('/^[a-f0-9]{32}$/i', $this->config['password'])) {
            Log::warning(__METHOD__ . ': SMSC_PASSWORD_IS_MD5=true, но пароль не похож на MD5-хеш');
        }

        return [
            'login' => $this->config['login'],
            'psw' => $this->config['password'],
        ];
    }

    /**
     * SMSC принимает номер в международном формате с «+» (без «+» он пытается
     * угадать код страны сам). В базе телефон лежит как E.164 — валидация во
     * всех Request'ах требует starts_with:+, — поэтому просто чистим
     * разделители и возвращаем плюс на место.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return $digits === '' ? '' : '+' . $digits;
    }
}
