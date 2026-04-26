<?php

namespace Zeaven\EasySuit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Zeaven\EasySuit\Services\AesCipher;

class EncryptJsonTransport
{
    public function handle(Request $request, Closure $next)
    {
        $key = config('app.key');

        // 解密请求
        if ($request->isJson()) {
            $payload = $request->json()->all();

            if (isset($payload['cipher'], $payload['iv'], $payload['tag'])) {
                $data = AesCipher::decrypt(
                    $payload['cipher'],
                    $payload['iv'],
                    $payload['tag'],
                    $key
                );

                // 替换为解密后的数据
                $request->merge($data);
            }
        }

        $response = $next($request);

        // 只加密 JSON 响应
        if ($response->headers->get('content-type') === 'application/json') {
            $data = json_decode($response->getContent(), true);

            $encrypted = AesCipher::encrypt($data, $key);

            return response()->json($encrypted);
        }

        return $response;
    }
}
