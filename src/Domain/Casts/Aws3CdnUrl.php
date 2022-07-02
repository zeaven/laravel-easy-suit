<?php

/**
 * Cdn地址转换
 * 使用
 * protected $casts = [
 *     'uri' => CdnUrl::class,  // 转换成cdn地址
 *     'cover_uri' => CdnUrl::class. ':cdn,true', // 转换成cdn地址并生成有效期访问地址
 *     'id_card' => CdnUrl::class. ':image,true'， // 转换成image地址并授权访问
 * ]
 *
 * @date    2020-06-19 16:25:27
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Str;
use Storage;

class Aws3CdnUrl implements CastsAttributes
{
    public static $cloud;
    // 转换方式，默认cdn，有image还有zip，根据services配置添加其他方式
    protected $type;
    // 是否授权访问，默认不授权，true、false，
    protected $auth;

    public function __construct($type = null, $auth = null)
    {
        $this->type = $type ?: '';
        $this->auth = boolval($auth ?: false);
        if ($this->auth && empty(static::$cloud)) {
            static::$cloud = \Storage::cloud();
        }
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
        $url = stripcslashes($url);

        if (blank($url)) {
            return $url;
        } elseif (Str::startsWith($url, $cdn_host)) {
        } elseif (Str::startsWith($url, 'http')) {
            // 转换 url 地址域名为 cdn 域名
            $url = preg_replace('/^http.*\.(com|cn|net)\//', $cdn_host, $url);
        } elseif ($this->auth) {
            $url = static::$cloud->temporaryUrl($url, now()->addHour());
        } else {
            $url = $cdn_host . trim($url, '/');
        }
        return $url;
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
                    $value[$idx] = preg_replace('/^http.*\.(com|cn|net)/', '', $item);
                }
            }
        } else {
            $value = stripcslashes($value);
            $value = urldecode($value);
            $value = preg_replace('/\?.*$/', '', $value);
            if (Str::startsWith($value, 'http')) {
                // 转换 url 地址域名为 cdn 域名
                $value = preg_replace('/^http.*\.(com|cn|net)/', '', $value);
            }
        }
        return $value;
    }
}
