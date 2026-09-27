<?php

namespace App\Services\Billing;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

/** Google Play Developer API от имени сервисного аккаунта. */
class GooglePlayClient
{
    private const BASE = 'https://androidpublisher.googleapis.com/androidpublisher/v3/applications/';

    /** purchases.subscriptionsv2.get — источник правды о подписке. */
    public function subscription(string $purchaseToken): array
    {
        return Http::withToken($this->accessToken())
            ->get($this->app() . '/purchases/subscriptionsv2/tokens/' . $purchaseToken)
            ->throw()
            ->json();
    }

    /** Без подтверждения за 3 дня Google сам возвращает деньги. */
    public function acknowledge(string $productId, string $purchaseToken): void
    {
        Http::withToken($this->accessToken())
            ->withBody('{}', 'application/json')
            ->post($this->app() . "/purchases/subscriptions/{$productId}/tokens/{$purchaseToken}:acknowledge")
            ->throw();
    }

    protected function accessToken(): string
    {
        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/androidpublisher',
            config('billing.google.service_account'),
        );

        return $credentials->fetchAuthToken()['access_token'];
    }

    private function app(): string
    {
        return self::BASE . config('billing.google.package_name');
    }
}
