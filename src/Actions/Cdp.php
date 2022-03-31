<?php

namespace Cellphones\Cdp\Actions;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

class Cdp
{
    public const CDP_AUTH_URL = 'https://account.mydatalakes.com/api/accounts/authen';
    public const CDP_PUSH_URL = 'https://svc.mydatalakes.com/dhub-i/api/v1.0/ingest';
    public const CDP_AMOUNT_PER_PUSH = 50;
    public const CDP_SOURCE = 'CPS-Website';
    public const CDP_RECORD_TIMEZONE_GMT = 7;
    public const CDP_RECORD_SOURCE = 'Online';

    public string $accessToken;
    public int $expiredAt;
    public string $cdpUsername;
    public string $cdpGKey;
    public string $cdpApiKey;
    public int $pushDay;
    public array $errors = [];
    public array $elasticResult;
    public LazyCollection $collections;


    public function __construct(LazyCollection $collections)
    {
        $this->cdpUsername = config('cdp.cdp.username');
        $this->cdpGKey = config('cdp.cdp.gkey');
        $this->cdpApiKey = config('cdp.cdp.api_key');
        $this->pushDay = (int)config('cdp.cdp.push_day');
        $this->collections = $collections;
    }

    public function processElasticResult()
    {
        $arrProduct = $this->elasticResult['hits']['hits'];
        $collections = LazyCollection::make(function () use ($arrProduct) {
            foreach ($arrProduct as $product) {
                yield $product;
            }
        });
        $this->collections = $this->collections->merge($collections);
    }

    public function authentication(): bool
    {
        $response = Http::post(self::CDP_AUTH_URL, [
            'username' => $this->cdpUsername,
            "apiKey" => $this->cdpApiKey,
            "cdpGKey" => $this->cdpGKey,
        ]);
        $responseObject = $response->object();
        if ($response->status() !== 200 || $responseObject->success == false) {
            $this->errors[] = 'Authentication error: ' . $responseObject->message;

            return false;
        }

        if (empty($responseObject->token)) {
            $this->errors[] = 'Missing access token';

            return false;
        }

        if (empty($responseObject->expiredAt)) {
            $this->errors[] = 'Missing expired time';

            return false;
        }

        $this->setToken($responseObject->token);
        $this->setTokenExpired($responseObject->expiredAt);

        return true;
    }

    public function setToken($token)
    {
        $this->accessToken = $token;
    }

    public function setTokenExpired($expiredAt)
    {
        $this->expiredAt = $expiredAt;
    }

    public function pushToCDP(array $items, $objectType): Response
    {
        $auth = $this->authentication();
        if (strtotime(now()) > $this->expiredAt) {
            if (!$auth) {
                Log::error("ERROR DTV Trans: " . implode(PHP_EOL, $this->errors));
                exit();
            }
        }
        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'TOKEN' => $this->accessToken,
            'API_KEY' => $this->cdpApiKey,
        ])->post(self::CDP_PUSH_URL, [
            'objectType' => $objectType,
            'source' => self::CDP_SOURCE,
            'entries' => $items,
        ]);
    }
}