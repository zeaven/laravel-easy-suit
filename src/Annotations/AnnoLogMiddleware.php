<?php

namespace Zeaven\EasySuit\Annotations;

use App;
use Zeaven\EasySuit\Annotations\AnnoLog;
use Arr;
use Browser;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnoLogMiddleware
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        if (
            config('easy_suit.anno_log.enable') !== true
            || $response instanceof StreamedResponse
            || $response instanceof BinaryFileResponse
            || App::runningInConsole()
        ) {
            return;
        }
        $route = request()->route();
        $user = auth()->user() ?? [];

        $data = [];
        $data['status'] = $response instanceof Response ? $response->getStatusCode() : $response->status();
        if (in_array($request->method(), ['GET', 'POST', 'PUT', 'DELETE'])) {
            $data['user_id'] = $user['uid'] ?? '';
            $data['controller'] = $route->getActionName();
            $data['method'] = $request->method();
            $data['url'] = str_replace($request->url(), $request->path(), $request->fullUrl());
            $data['params'] = json_encode($request->all());
            $data['time'] = date('Y-m-d H:i:s');
            $data['ip'] = request()->ip;
            $data['referrer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $request->fullUrl();
            $data['data'] = ($response instanceof JsonResponse) ? $response->getData(true) : $response->getContent();
            if (is_string($data['data'])) {
                $data['data'] = json_decode($data['data'], true);
            }
            $data['app_name'] = config('app.name', '');
        }

        // 处理
        rescue(
            function () use ($route, $data) {
                $this->captureUserLog($route->getAction(), $data);
            },
            function ($e) use ($data) {
                logs('daily')->error($e->getMessage(), [$e->getTraceAsString()]);
            },
            false
        );
    }

    /**
     * 记录用户日志
     * @param User $user 用户对象
     * @param array $data 日志数据
     * @return [type]          [description]
     */
    private function captureUserLog($action, array $data)
    {
        $action = $action['uses'];
        // 判断成功或失败
        $annotation = AnnoLog::annotation($action);
        if (empty($annotation)) {
            return;
        }
        $log = Arr::pull($annotation, 'log');

        // $success = intval($data['status']) === 200 && intval(Arr::get($data, 'data.code', 2)) === 0;
        if (class_exists('Browser')) {
            $browser = Browser::detect();
            $data['device'] = $browser->platformName() . ', ' . $browser->deviceFamily();
        }

        $logHandler = config('easy_suit.anno_log.handler');
        if ($logHandler && class_exists($logHandler)) {
            (new $logHandler)->handle($log, $annotation + $data);
        } else {
            logger($log, $annotation + $data);
        }
    }
}
