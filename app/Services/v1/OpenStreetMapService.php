<?php

namespace App\Services\v1;

use App\Presenters\v1\OpenStreetMapPresenter;
use App\Services\BaseService;
use GuzzleHttp\Client;

class OpenStreetMapService extends BaseService
{
    protected string $baseUrl;

    public function __construct() {
        // Example request
        // https://nominatim.openstreetmap.org/search?q=%D0%9A%D0%B0%D1%80%D0%B0%D0%B3%D0%B0%D0%BD%D0%B4%D0%B0&format=json&polygon=1&addressdetails=1
        $this->baseUrl = 'https://nominatim.openstreetmap.org';
    }
    public function findAddress(array $data)
    {
        $response = $this->sendRequest($data['address']);

        if ($response->getStatusCode() != 200) {
            return $this->errService(__('geo.address_failed'));
        }

        $responseData = json_decode($response->getBody()->getContents(), true);

        return $this->resultCollections(
            $responseData,
            OpenStreetMapPresenter::class,
            'list'
        );
    }

    private function sendRequest(string $query)
    {
        $params = [
            'query' => [
                'q' => $query,
                'format' => 'json',
                'polygon' => 1,
                'addressdetails' => 1,
            ],
        ];
        $client = new Client([
            'base_uri' => $this->baseUrl,
        ]);

        $response = $client->get('search', $params);

        return $response;
    }
}