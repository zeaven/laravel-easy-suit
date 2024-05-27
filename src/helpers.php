<?php

use Zeaven\EasySuit\Annotations\AnnoLog;
use Zeaven\EasySuit\Exceptions\Exception;

//成功返回json格式数据
if (!function_exists('ok')) {
    function ok($list = null, array $headers = [])
    {
        if (blank($list)) {
        } elseif ($list instanceof Symfony\Component\HttpFoundation\Response) {
            return $list;
        } elseif (is_a($list, Illuminate\Support\Collection::class)) {
            $list = compact('list');
        } elseif (is_array($list)) {
            array_key_exists(0, $list) && $list = compact('list');
        } elseif ($list instanceof Zeaven\EasySuit\Http\ResponseMappers\BaseResponseMapper) {
            if ($list->isArray()) {
                $list = [
                    'list' => $list->toArray()
                ];
            }
        } elseif (!is_object($list)) {
            $list = [
                'result' => $list
            ];
        }
        return response()->json($list)->withHeaders($headers);
    }
}


/**
 * 异常抛出
 * throw_e(500)
 * throw_e('message', 500)
 */
if (!function_exists('throw_e')) {
    function throw_e($err, int|array $code = 500, ?array $data = null)
    {
        if (is_array($code)) {
            [$code, $data] = [500, $code];
        }
        if (is_numeric($err)) {
            [$err, $code] = ['', $err];
        }
        if ($err instanceof Exception) {
            throw $err;
        } else {
            request()->errorData($data);
            if ($code > 550) {
                throw new Exception(strval($err), 500, null, $code);
            } else {
                throw new Exception(strval($err), $code, null, $code);
            }
        }
    }
}
// 条件异常
if (!function_exists('throw_on')) {
    function throw_on($bool, $err, int|array $code = 500, ?array $data = null)
    {
        if ($bool) {
            throw_e($err, $code, $data);
        }
        return $bool;
    }
}
// 空异常
if (!function_exists('throw_empty')) {
    function throw_empty($empty, $err, int|array $code = 500, ?array $data = null)
    {
        if (empty($empty)) {
            throw_e($err, $code, $data);
        }
        return $empty;
    }
}

if (! function_exists('common_path')) {
    function common_path(string $path = '')
    {
        return app_path('Common/' . $path);
    }
}

if (! function_exists('anno_log')) {
    function anno_log($key, $value = null)
    {
        AnnoLog::data($key, $value);
    }
}


/**
 * 比较两个数字的大小
 * @param  int|string  $a
 * @param  int|string  $b
 * @param  string  $operator  比较符号，'=','>=','>','<','!='
 * @return [type]           [description]
 */
function _bccomp($a, $b, $operator = '=')
{
    if (function_exists('bccomp')) {
        return version_compare(bccomp(strval($a), strval($b), 6), 0, $operator);
    } else {
        $result = strcmp(strval(round(floatval($a), 6)), strval(round(floatval($b), 6)));
        return version_compare($result, 0, $operator);
    }
}

function _bcadd($a, $b, $scale = 2)
{
    return round(floatval(bcadd($a, $b, 5)), $scale);
}

function _bcsub($a, $b, $scale = 2)
{
    return round(floatval(bcsub($a, $b, 5)), $scale);
}

function _bcmul($a, $b, $scale = 2)
{
    if (function_exists('bcmul')) {
        return round(floatval(bcmul($a, $b, 5)), $scale);
    } else {
        return round(floatval($a) * floatval($b), $scale);
    }
}

function _bcdiv($a, $b, $scale = 2)
{
    return round(floatval(bcdiv($a, $b, 5)), $scale);
}

if (!function_exists('size_convert')) {
    function size_convert(int $bytes, string $type = '', int $decimals = 2): float
    {
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];

        if (empty($type)) {
            $floor = floor((strlen(strval($bytes)) - 1) / 3);
        } else {
            $floor = array_search($type, $size);
        }

        return round(($bytes / pow(1024, $floor)), $decimals); // . $size[$floor];
    }
}

if (!function_exists('sentry')) {
    function sentry($msg, array $extra = [])
    {
        if (!app()->bound('sentry') || App::environment('local')) {
            Log::error(($msg instanceof \Exception) ? $msg->getMessage() : $msg, $extra);
        } else {
            $sentry = app('sentry');
            \Sentry\configureScope(
                function (\Sentry\State\Scope $scope) use ($extra): void {
                    // Add user context
                    if (auth()->check()) {
                        $scope->setUser(
                            auth()->user()->toArray()
                        );
                    }
                    foreach ($extra as $key => $value) {
                        $scope->setExtra($key, $value);
                    }
                }
            );
            if ($msg instanceof \Exception) {
                $sentry->captureException($msg);
            } else {
                $sentry->captureMessage($msg);
            }
        }
    }
}


function base64_to_safe($data)
{
    return rtrim(strtr($data, '+/', '-_'), '=');
}

function base64_to_unsafe($data)
{
    return str_pad(strtr($data, '-_', '+/'), strlen($data) + (strlen($data) % 4), '=', STR_PAD_RIGHT);
}


if (!function_exists('mb_explode')) {
    // 中文字符串分隔
    function mb_explode(string $deliter, string $string)
    {
        $array = [0 => ''];
        $step = 0;
        for ($i = 0, $l = mb_strlen($string) - 1; $i <= $l; $i++) {
            $value = mb_substr($string, $i, 1);
            if ($value === $deliter) {
                $step++;
            } elseif (empty($deliter)) {
                $array[$step++] = ($array[$step] ?? '') . $value;
            } else {
                $array[$step] = ($array[$step] ?? '') . $value;
            }
        }
        return array_values($array);
        // return array_map(function ($i) use ($string) {
        //     return mb_substr($string, $i, 1);
        // }, range(0, mb_strlen($string) -1));
    }
}


if (!function_exists('db_trans')) {
    // 开启事务
    // db_trans(['mysql',...], callback)
    // db_trans(callback);
    function db_trans($conns, $closure = null)
    {
        throw_empty($conns, '开启事务失败');
        if (is_callable($conns)) {
            $closure = $conns;
            $conns = [];
        }
        if (empty($conns)) {
            return Illuminate\Support\Facades\DB::transaction($closure);
        } else {
            $outerConn = array_pop($conns);
            foreach ($conns as $conn) {
                $inner_closure = $closure;
                $closure = function () use ($inner_closure) {
                    return Illuminate\Support\Facades\DB::connection($conn)->transaction($inner_closure);
                };
            }
            return Illuminate\Support\Facades\DB::connection($outerConn)->transaction($closure);
        }
    }
}

if (!function_exists('locker')) {
    /**
     * redis锁
     * @param  string  $name  锁名，保证唯一性
     * @param  callable  $closure
     * @param  integer  $expire  过期时间(秒)，超过时间任务未完成，将无法阻塞其他任务
     * @param  integer  $wait  等待时间(秒)，超过时间无法获得涣，将抛异常
     * @return [type]          [description]
     */
    function locker(string $name, callable $closure, int $expire = 10, int $wait = 10)
    {
        return cache()->lock($name, $expire)->block($wait, $closure);
    }
}

if (!function_exists('locker_trans')) {
    /**
     * redis锁，并开始数据库事务
     * @param string $name 锁名，保证唯一性
     * @param callable $closure
     * @param integer $expire 过期时间(秒)，超过时间任务未完成，将无法阻塞其他任务
     * @param integer $wait 等待时间(秒)，超过时间无法获得涣，将抛异常
     * @return [type]          [description]
     */
    function locker_trans(string $name, callable $closure, int $expire = 10, int $wait = 20)
    {
        return locker($name, function () use ($closure) {
            return db_trans($closure);
        }, $expire, $wait);
    }
}
