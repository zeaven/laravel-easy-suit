<?php

/**
 * Cdn地址转换
 * 使用
 * protected $casts = [
 *     'uri' => CdnUrl::class,  // 转换成cdn地址
 *     'cover_uri' => CdnUrl::class. ':cdn,auth_key', // 转换成cdn地址并生成有效期访问地址
 *     'id_card' => CdnUrl::class. ':cdn,encrypt'， // 转换成cdn地址并加密，客户端需要解密
 *     ‘avatar' => CdnUrl::class. ':cdn,auth_key,60' // 转换成cdn地址并裁剪成宽度为60
 * ]
 *
 * @date    2020-06-19 16:25:27
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Str;

class CdnUrl implements CastsAttributes
{
    // 转换方式，默认cdn，有image还有zip，根据services配置添加其他方式
    protected $type;
    // 加密方式，默认不加密，有cdn的auth_key，和encrypt
    protected $auth;
    // 缩放, 整数，如传入1080，则x-oss-process=image/resize,w_1080,
    protected $resize;

    public function __construct($type = null, $auth = null, $resize = null)
    {
        $this->type = $type;
        $this->auth = $auth;
        $this->resize = $resize;
    }

    /**
     * 将取出的数据进行转换
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return \App\Address
     */
    public function get($model, $key, $value, $attributes)
    {
        $cdn_host = config("services.cdn.{$this->type}_url", config("services.cdn.url"));
        $cdn_host = Str::finish($cdn_host, '/');
        if (is_array($value)) {
            foreach ($value as $idx => $item) {
                $value[$idx] = $this->convertUrl($item, $cdn_host);
            }
        } else {
            $value = $this->convertUrl($value, $cdn_host);
        }

        return $value;
    }

    private function convertUrl($url, $cdn_host)
    {
        $type = $this->type;
        $encrypt = $this->auth;
        $resize = $this->resize;
        $url = stripcslashes($url);
        if (blank($url)) {
            return $url;
        } elseif (Str::startsWith($url, $cdn_host)) {
        } elseif (Str::startsWith($url, 'http')) {
            // 转换 url 地址域名为 cdn 域名
            $url = preg_replace('/^http.*\.(com|cn)\//', $cdn_host, $url);
        } else {
            $url = $cdn_host . trim($url, '/');
        }
        // cdn url auth
        if ($auth_key = config("services.cdn.{$encrypt}")) {
            $url = static::cdnURLAuth($url);
        }
        if ($resize) {
            $prefix = strpos($url, '?') === false ? '?' : '&';
            $url .= "{$prefix}x-oss-process=image/resize,w_" . $resize;
        }
        if ($encrypt === 'encrypt' && config("services.cdn.encrypt")) {
            $url = encrypt($url);
        }
        return $url;
    }

    public static function cdnURLAuth(string $url)
    {
        $key = config("services.cdn.auth_key");
        $url = implode(
            '/',
            array_map(
                function ($item) {
                    return strpos($item, 'http') !== false ? $item : urlencode($item);
                },
                explode('/', $url)
            )
        );
        $uri = parse_url($url, PHP_URL_PATH);
        $timestamp = time() + 3600;
        $rand = 0; //preg_replace('/\-/', '', Str::uuid()->toString());
        $uid = 0;
        $md5hash = md5("{$uri}-{$timestamp}-{$rand}-{$uid}-{$key}");
        $auth_key = "{$timestamp}-{$rand}-{$uid}-{$md5hash}";
        return $url . (strpos($url, '?') !== false ? '&' : '?') . "auth_key={$auth_key}";
    }

    /**
     * 转换成将要进行存储的值
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  \App\Address  $value
     * @param  array  $attributes
     * @return array
     */
    public function set($model, $key, $value, $attributes)
    {
        if (is_array($value)) {
            foreach ($value as $idx => $item) {
                $item = stripcslashes($item);
                $item = urldecode($item);
                $value = preg_replace('/\?.*$/', '', $value);
                if (Str::startsWith($item, 'http')) {
                    // 转换 url 地址域名为 cdn 域名
                    $value[$idx] = preg_replace('/^http.*\.(com|cn)/', '', $item);
                }
            }
        } else {
            $value = stripcslashes($value);
            $value = urldecode($value);
            $value = preg_replace('/\?.*$/', '', $value);
            if (Str::startsWith($value, 'http')) {
                // 转换 url 地址域名为 cdn 域名
                $value = preg_replace('/^http.*\.(com|cn)/', '', $value);
            }
        }
        return $value;
    }
}
