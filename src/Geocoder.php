<?php

namespace Spatie\Geocoder;

use Exception;
use GuzzleHttp\Client;

class Geocoder
{
    const RESULT_NOT_FOUND = 'result_not_found';

    /** @var \GuzzleHttp\Client */
    protected $client;

    /** @var string */
    protected $endpoint = 'https://maps.googleapis.com/maps/api/geocode/json';

    /** @var string */
    protected $apiKey;

    /** @var string */
    protected $language;

    /** @var string */
    protected $region;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function setApiKey(string $apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function setLanguage(string $language)
    {
        $this->language = $language;

        return $this;
    }

    public function setRegion(string $region)
    {
        $this->region = $region;

        return $this;
    }

    public function getCoordinatesForAddress(string $address): array
    {
        if (empty($address)) {
            return $this->emptyResponse();
        }

        $payload = $this->getRequestPayload(compact('address'));

        $response = $this->client->request('GET', $this->endpoint, $payload);

        if ($response->getStatusCode() !== 200) {
            throw new Exception('could not connect to googleapis.com/maps/api');
        }

        $geocodingResponse = json_decode($response->getBody());

        // Exception for google api error response
        if (isset($geocodingResponse->status) && $geocodingResponse->status != "OK" && isset($geocodingResponse->error_message)) {
            throw new Exception($geocodingResponse->status . ' : ' . $geocodingResponse->error_message);
        }

        if (! count($geocodingResponse->results)) {
            return $this->emptyResponse();
        }

        return $this->formatResponse($geocodingResponse);
    }

    public function getAddressForCoordinates(float $lat, float $lng): array
    {
        $payload = $this->getRequestPayload([
            'latlng' => "$lat,$lng",
        ]);

        $response = $this->client->request('GET', $this->endpoint, $payload);

        if ($response->getStatusCode() !== 200) {
            throw new Exception('could not connect to googleapis.com/maps/api');
        }

        $reverseGeocodingResponse = json_decode($response->getBody());

        // Exception for google api error response
        if (isset($reverseGeocodingResponse->status) && $reverseGeocodingResponse->status != "OK" && isset($geocodingResponse->error_message)) {
            throw new Exception($reverseGeocodingResponse->status . ' : ' . $reverseGeocodingResponse->error_message);
        }

        if (! count($reverseGeocodingResponse->results)) {
            return $this->emptyResponse();
        }

        return $this->formatResponse($reverseGeocodingResponse);
    }

    protected function formatResponse($response): array
    {
        return [
            'lat' => $response->results[0]->geometry->location->lat,
            'lng' => $response->results[0]->geometry->location->lng,
            'accuracy' => $response->results[0]->geometry->location_type,
            'formatted_address' => $response->results[0]->formatted_address,
        ];
    }

    protected function getRequestPayload(array $parameters): array
    {
        $parameters = array_merge([
            'key' => $this->apiKey,
            'language' => $this->language,
            'region' => $this->region,
        ], $parameters);

        return ['query' => $parameters];
    }

    protected function emptyResponse(): array
    {
        return [
            'lat' => 0,
            'lng' => 0,
            'accuracy' => static::RESULT_NOT_FOUND,
            'formatted_address' => static::RESULT_NOT_FOUND,
        ];
    }
}
