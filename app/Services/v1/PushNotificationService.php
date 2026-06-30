<?php

namespace App\Services\v1;

use App\Services\BaseService;
use Illuminate\Support\Facades\Log;

class PushNotificationService extends BaseService
{
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = config('firebase.apikey');
    }

    public function sendNotification($tokens, $title, $body, array $sendData = []): bool
    {
        Log::info(__METHOD__ . ' title:' . $title . ', body:' . $body, $sendData);

        $data = [
            "notification" => [
                "title" => $title,
                "body" => $body,
            ],
        ];
        if (is_array($tokens)) {
            $data['registration_ids'] = $tokens;
            Log::info(__METHOD__ . ' ' . count($tokens) . ' recepients');
        } else {
            $data['to'] = $tokens;
            Log::info(__METHOD__ . ' recepient: ' . $tokens);
        }

        if (count($sendData)) {
            $data['data'] = $sendData;
        }

        $headers = [
            "Authorization: key=" . $this->apiKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        Log::info(__METHOD__ . ' response:' . $response);

        if (curl_errno($ch)) {
            Log::error(__METHOD__ . ' curl_error:' . curl_error($ch));
        }
        curl_close($ch);

        if (json_decode($response) != null) {
            return true;
        }

        return false;
    }
}