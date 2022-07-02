<?php

/**
 * oss 图片处理程序
 *
 * @date    2020-09-11 09:39:06
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Casts;

use Zeaven\EasySuit\Domain\Casts\CdnUrl;
use Illuminate\Database\Eloquent\Model;

class OssImgProcess extends CdnUrl
{
    public function __construct($type = null, $auth = null)
    {
        parent::__construct($type, $auth);
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
        $value = parent::get($model, $key, $value, $attributes);

        // 进行剪裁
        $value = $this->crop($model, $value);

        return $value;
    }

    /**
     * 剪裁图片
     * x-oss-process=image/crop,x_1565,y_274,w_139,h_144
     * @return [type] [description]
     */
    protected function crop(Model $model, string $url)
    {
        if (!$model->avatar_location || !isset($model->avatar_location['top'])) {
            return $value;
        }
        ['top' => $y, 'right' => $right, 'bottom' => $bottom, 'left' => $x] = $model->avatar_location ?? [];
        $x = intval($x);
        $y = intval($y);
        $w = intval($right - $x);
        $h = intval($bottom - $y);
        $prefix = strpos($url, '?') === false ? '?' : '&';

        return "{$url}{$prefix}x-oss-process=image/crop,x_{$x},y_{$y},w_{$w},h_{$h}";
    }
}
