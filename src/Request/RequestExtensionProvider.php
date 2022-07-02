<?php

namespace Zeaven\EasySuit\Request;

use Illuminate\Support\ServiceProvider;
use Request;
use Symfony\Component\Finder\Finder;

/**
 * 用法：
 * App\Http\Requests\AdminRequest
 * $request->fields(['parameter1'=>['default'=>'123','rule'=>'required|integer','as'=>'alias','type'=>'int'],'parameter2'=>12,'paramter3'])
 * 或在AdminRequest的rule属性进行配置后
 * $request->params(null|false) => 返回参数value数组
 * $request->params(true) => 返回参数key/value数组
 * $request->params(['a','b']) => 返回指定key的参数key/value数组
 * $request->params(OtherClass) => 将参数key/value数组传入OtherClass构造函数，并返回OtherClass实例
*/

class RequestExtensionProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        Request::macro(
            'fields',
            function (array $arguments, bool $removeKeys = true) {
                $requestExtend = new RequestExtension($this);
                $result = $requestExtend->get($arguments);
                return !$removeKeys ? $result : array_values($result);
            }
        );

        Request::macro(
            'params',
            function ($param_class = null) {
                $requestExtend = new RequestExtension($this);
                $result = $requestExtend->values();
                if (blank($param_class) || $param_class === false) {
                    return array_values($result);
                } elseif ($param_class === true) {
                    return $result;
                } elseif (is_array($param_class)) {
                    $res = [];
                    foreach ($param_class as $value) {
                        $res[] = isset($result[$value]) ? $result[$value] : null;
                    }
                    return $res;
                } elseif (class_exists($param_class)) {
                    return new $param_class($result);
                }
            }
        );

        Request::macro(
            'values',
            function () {
                return $this->params(false);
            }
        );

        Request::macro(
            'getSort',
            function () {
                $sorter = $this->get('sorter') ?? '';
                return json_decode($sorter, true) ?: [];
            }
        );
    }
}
