<?php
namespace Zeaven\EasySuit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Zeaven\EasySuit\Services\CryptJson;

class CryptJsonTransport
{
    public function handle(Request $request, Closure $next)
    {
        // 解密请求
        if ($request->isJson()) {
            $payload = $request->json()->all();

            if (isset($payload['cipher'])) {
                $data = app(CryptJson::class)::decrypt($payload['cipher']);
                $request->merge($data);
            }
        }

        $response = $next($request);

        // 仅加密 JSON 响应
        if (str_starts_with(
            (string) $response->headers->get('content-type'),
            'application/json'
        )) {
            $data = json_decode($response->getContent(), true);

            $encrypted = app(CryptJson::class)->encrypt($data);

            return response()->json(['cipher' => $encrypted]);
        }

        return $response;
    }
}
