<?php

/**
 * postman client
 *
 * @date    2019-04-11 13:52:34
 * @version $Id$
 */

namespace Zeaven\EasySuit\Services;

use GuzzleHttp\Client;
use Exception;

class PostmanClient
{
    private $xApiKey;
    private $client;

    public function __construct()
    {
        $this->xApiKey = config('easy_suit.postman.token');

        $this->client = new Client([
            'base_uri' => 'https://api.getpostman.com/',
            // 'timeout' => 10,
            'debug' => false,
            'headers' => [
                'X-Api-Key' => $this->xApiKey
            ]
        ]);
    }

    public function request(string $method, string $url, array $params = [])
    {
        $response = $this->client->request($method, $url, $params);
        if ($response->getStatusCode() === 200) {
            $body = $response->getBody();
            $result = json_decode((string)$body, true);
            return $result;
        } else {
            throw_e(new Exception('请求失败：' . $url));
        }
    }

    /**
     * postmanClient->collects('get', 'id')
     * @param string $method [description]
     * @param array $arguments [description]
     * @return [type]            [description]
     */
    public function __call(string $method, array $arguments)
    {
        $method = strtolower($method);
        if (in_array($method, ['get', 'post', 'put', 'delete'])) {
            return $this->request($method, ...$arguments);
        }
        throw new Exception('方法不存在:' . $method);
    }
}
