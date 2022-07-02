<?php

namespace Zeaven\EasySuit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Arr;

class ApiLocale
{
    /**
     * 多语言配置表
     * [ 'laravel语言包标识' => 'accept-language传递的值'] 如
     * [ 'zh_CN' => 'zh'] 或 [ 'zh_CN' => ['zh', 'zh-CN']]
     */
    const LANGUAGES = ['zh_CN' => ['zh','zh-CN'],'en'];
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,ja;q=0.7,zh-TW;q=0.6,it;q=0.5
        $acc_lang = $request->headers->get('Accept-Language');
        if ($acc_lang) {
            $languages = collect(explode(',', $acc_lang))
                ->map(fn($item) => explode(';', $item)[0])
                ->map(fn($item) => str_replace('_', '-', strtolower($item)))
                ->toArray();

            foreach (static::LANGUAGES as $locale => $lans) {
                if (is_numeric($locale)) {
                    $locale = $lans;
                }
                $matcheds = array_intersect($languages, Arr::wrap($lans));
                if (!empty($matcheds)) {
                    app()->setLocale($locale);
                    break;
                }
            }
        }
        return $next($request);
    }
}
