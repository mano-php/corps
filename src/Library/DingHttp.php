<?php

namespace ManoCode\Corp\Library;

use Exception;
use Illuminate\Support\Facades\Http;
use ManoCode\Corp\CorpsServiceProvider;
use ManoCode\Corp\Services\DingService;

/**
 * HTTP 封装类
 *
 * @method static mixed get(string $url, array $params = [], string $client_id = '', string $client_secret = '') 发送 GET 请求
 * @method static mixed post(string $url, array $params = [], string $client_id = '', string $client_secret = '') 发送 POST 请求
 * @method static mixed put(string $url, array $params = [], string $client_id = '', string $client_secret = '') 发送 POST 请求
 * @method static mixed patch(string $url, array $params = [], string $client_id = '', string $client_secret = '') 发送 POST 请求
 */
class DingHttp
{
    public static function __callStatic($method, $arguments)
    {
        $url = $arguments[0];
        $params = $arguments[1] ?? [];
        $client_id = $arguments[2] ?? '';
        $client_secret = $arguments[3] ?? '';

        // Check if the method is GET, POST, PUT, DELETE, or PATCH
        if (!in_array(strtolower($method), ['get', 'post', 'put', 'delete', 'patch'])) {
            throw new \Exception("Unsupported method {$method}");
        }

        $client = new Client();

        try {
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
            ];
            // Check if the URL is for DingTalk v2 API
            if (str_contains($url, 'api.dingtalk.com/v1.0')) {
                $options['headers']['x-acs-dingtalk-access-token'] = self::getAccessToken($client_id, $client_secret);
                $options['json'] = $params;
            } else {
                $options['query'] = ['access_token' => self::getAccessToken($client_id, $client_secret)];
            }
            $response = $client->request(strtoupper($method), $url, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \Exception("HTTP request failed: " . $e->getMessage());
        }
    }


    private static function getAccessToken($client_id = '', $client_secret = '')
    {
        $client_id = $client_id ?: CorpsServiceProvider::setting('client_id');
        $client_secret = $client_secret ?: CorpsServiceProvider::setting('client_secret');
        return DingService::getAccessToken($client_id, $client_secret);
    }
}
