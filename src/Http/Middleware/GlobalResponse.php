<?php

namespace Zeaven\EasySuit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Str;
use ErrorCode;
use Arr;

class GlobalResponse
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($except_routes = config('easy_suit.global_response.exclude')) {
            foreach ($except_routes as $except_route) {
                if ($request->is($except_route)) {
                    $request->attributes->set('_global_response', false);
                    return $next($request);
                }
            }
        }
        $request->attributes->set('_global_response', true);
        $response = $next($request);

        $enable_debugbar = (app()->bound('debugbar') && app('debugbar')->isEnabled()) ? ['_debugbar' => app('debugbar')->getData()] : [];
        [$enable_debugbar, $sqlSlow] = $this->filterDebugInfo($enable_debugbar);

        $code = $response->getStatusCode();
        $response->setStatusCode(200);

        if ($response instanceof JsonResponse) {
            $data = $this->wrapResponse($response->getData(true), $code);
            $sqlSlow && $data['sql'] = '检查到sql慢查询';
            $response->setData($data + $enable_debugbar);
        } elseif (Str::contains($response->headers->get('content-type'), 'json')) {
            $data = json_decode($response->getContent(), true) ?? null;
            $data = $this->wrapResponse($data, $code);
            $sqlSlow && $data['sql'] = '检查到sql慢查询';
            $response->setContent(json_encode($data + $enable_debugbar, JSON_UNESCAPED_UNICODE));
        }

        return $response;
    }

    public function wrapResponse($data, $code)
    {
        $result = [];
        // [$error_code, $error_msg] = array_values(ErrorCode::get());
        $result['code'] = $code === 200 ? 0 : $code;
        $result['data'] = null;
        $result['message'] = null;

        if (
            ($data instanceof ArrayAccess || is_array($data))
            && Arr::has($data, ['code', 'data', 'message'])
        ) {
            if ($data['code'] !== 0) {
                $result['message'] = ErrorCode::get($data['code'], $data['message']);
                $result['code'] = $data['code'];
            } else {
                $result['data'] = $this->emptyToNull($data['data']);
            }
            if (!app()->bound('debugbar')) {
                $result['error'] = $data['error'];
            }
            if (config('app.debug')) {
                $result['error'] = $data['error'];
            }
        } else {
            $result['data'] = $this->emptyToNull($data);
        }
        if (config('app.env') === 'production' || !config('app.debug')) {
            unset($result['error']);
        }

        $fields = config('easy_suit.global_response.fields', []);
        $response = [];
        foreach ($fields as $key => $value) {
            if (!$value || !array_key_exists($key, $result)) {
                continue;
            }
            $response[$value] = $result[$key];
        }

        return $response;
    }

    private function emptyToNull($data)
    {
        if (blank($data)) {
            return null;
        } elseif ($data instanceof ArrayAccess || is_array($data)) {
            foreach ($data as $key => $value) {
                if (blank($value)) {
                    $data[$key] = null;
                } elseif ($value instanceof ArrayAccess || is_array($data)) {
                    $data[$key] = $this->emptyToNull($value);
                }
            }
        }

        return $data;
    }

    private function filterDebugInfo(array $debugbar)
    {
        $slow = false;
        if (empty($debugbar)) {
            return [$debugbar, $slow];
        }

        $statements = data_get($debugbar, '_debugbar.queries.statements', []);

        foreach ($statements as &$state) {
            unset($state['backtrace']);
            if ($state['duration'] >= 0.5) {
                $slow = true;
            }
        }
        data_set($debugbar, '_debugbar.queries.statements', $statements);

        return [$debugbar, $slow];
    }
}
