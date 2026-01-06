<?php

/**
 * 把url所有key-value提取组成数组及排序后再合并成字符串
 * ?a=123&c=543&b=678
 * a123b678c543
 */

namespace Zeaven\EasySuit\Http\Middleware;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;

/**
 * 使用
 * middleware => ['api.sign']
 */
class ApiSignature
{
    /**
     * Handle an incoming request.
     * 接口签名验证
     * 签名方式：
     * 1. 将url参数提取为 key-value 数据并排序
     * 2. 去掉 signature 字段，剩下的数组按key排序，即ksort
     * 3. 将数组每一项 key-value 连接成字符串，如 ['a' => 1, 'b' => 2] = a1b2
     * 4. 对字符串做加密，然后做 base64_encode
     * 5. 最后做 base64_to_safe，在原来的base64_encode基础上对 "+"、"/" 替换为 "-","_", 对末尾的 "=" 去掉
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $query_array = $request->all();

        $signature = throw_empty(Arr::pull($query_array, 'signature'), '无效的签名');
        ksort($query_array);
        $query_str = collect($query_array)->map(function ($value, $key) {
            return $key . $value;
        })->implode('');
        // 加密方式必须是前后端一致
        $encrypt_data = base64_to_safe(Crypt::encryptString($query_str));

        throw_on($encrypt_data !== $signature, '接口签名失败');

        // TODO: 增加对时间戳的判断，误差大于30分钟认为无效
        // code...

        return $next($request);
    }
}
