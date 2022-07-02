<?php

/**
 * 输入类
 * Date: 2018/7/10
 * Time: 14:31
 *
 * 用法：
 * App\Http\Requests\AdminRequest
 * $request->fields(['parameter1'=>['default'=>'123','rule'=>'required|integer','as'=>'alias','type'=>'int'],'parameter2'=>12,'paramter3'])
 * 或在AdminRequest的rule属性进行配置后
 * $request->params(null|false) => 返回参数value数组
 * $request->params(true) => 返回参数key/value数组
 * $request->params(['a','b']) => 返回指定key的参数key/value数组
 * $request->params(OtherClass) => 将参数key/value数组传入OtherClass构造函数，并返回OtherClass实例
 *
 * 3种参数的输入形式
 *      1.不指定默认值的参数 如：'paramter'
 *      2.指定默认值的参数 如：'paramter'=>'默认值'
 *      3.指定默认值,别名,类型,验证规则的参数 如 'parameter1'=>['default'=>'123','rule'=>'required|integer','as'='alias']
 *      notice:以上1,2种方法，如果没有指定验证规则，则会调用App\Http\Requests\AdminRequest里面的默认验证规则
 *
 * 参数可选的属性
 * default:指定参数的默认值
 * rule:指定参数的验证规则
 *      rule输入形式：直接用laravel自带的验证规格，参考：https://laravel-china.org/docs/laravel/5.6/validation/1372#c58a91
 * as: 指定参数的别名，如"post_name"参数指定别名为"name",返回的数据的键名为"name"
 * type:指定参数的数据类型
 *      可选的值：
 *          -int  强制转换成int类型
 *          -float 强制转换成float类型
 *          -ip  ip类型
 *          -url url类型
 *          -email 邮件类型
 *          -split 将字符串转换成数组
 *          -array 将json数据转换成数组
 *          -json 将json数据转换成数组
 *          -date 将字符串转成Carbon日期
 *          -carbon 将字符串转成Carbon日期
 *
 *
 *
 *
 */

namespace Zeaven\EasySuit\Request;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class RequestExtension
{
    const VALID_STRINGS = [
        'email' => FILTER_VALIDATE_EMAIL,
        'url' => FILTER_VALIDATE_URL,
        'ip' => FILTER_VALIDATE_IP,
        'float' => FILTER_VALIDATE_FLOAT,
        'int' => FILTER_VALIDATE_INT,
        'bool' => FILTER_VALIDATE_BOOLEAN,

    ];
    private $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function get(array $arguments)
    {
        //解析
        $configs = $this->expalinConfigs($arguments);

        //验证参数的合法性
        $validate_rules = $this->coverToValidatorRules($configs);
        $messages = method_exists($this->request, 'messages') ? $this->request->messages() : [];
        $attributes = method_exists($this->request, 'attributes') ? $this->request->attributes() : [];
        try {
            $data = $this->request->validate($validate_rules, $messages, $attributes);
            //获取参数的默认值
            $result = $this->getRuleValues($data, $configs);
            return $result;
        } catch (Exception $e) {
            throw_e($e);
        } catch (ValidationException $e) {
            throw_e(head($e->errors())[0], 4);
        } catch (\Exception $e) {
            throw_e($e);
        }
    }

    public function values(): array
    {
        try {
            $data = $this->request->all();

            $configs = $this->request->getRuleConfig();
            return $this->getRuleValues($data, $configs);
        } catch (Exception $e) {
            throw_e($e);
        } catch (ValidationException $e) {
            throw_e(head($e->errors())[0], 4);
        } catch (\Exception $e) {
            throw_e($e);
        }
    }

    private function expalinConfigs(array $arguments)
    {
        // ['attribute1'=>['default'=>1,'rule'=>'required|array','as'=>'alias','type'=>'int' ],'attribute2','attribute3'=>3]
        return collect($arguments)->mapWithKeys(function ($item, $key) {
            if (is_numeric($key)) {
                $key = $item;
                $item = ['default' => null];
            } elseif (!is_array($item)) {
                $item = ['rule' => 'nullable', 'default' => $item];
            }
            return [$key => $item];
        })->toArray();
    }


    /**
     * 转换成laravel默认的验证规则
     * @param array $configs
     */
    private function coverToValidatorRules(array $configs)
    {
        if (method_exists($this->request, 'getRules')) {
            $request_rules = $this->request->getRules(array_keys($configs));
        } else {
            $request_rules = [];
        }
        foreach ($configs as $key => $config) {
            $request_rules[$key] = $config['rule'] ?? $request_rules[$key] ?? 'string';
        };
        return $request_rules;
    }

    private function getRuleValues(array $validate_data, array $configs)
    {
        $result = [];
        foreach ($configs as $key => $config) {
            $input = $validate_data[$key] ?? '';
            if (empty($config)) {
                $result[$key] = $input ?? '';
                continue;
            }
            if (!isset($config['urldecode']) || $config['urldecode'] !== false) {
                is_string($input) && $input = rawurldecode($input);
            }
            if (!isset($input) || blank($input)) {
                // 设默认值
                $input = array_key_exists('default', $config) ? $config['default'] : '';
            } elseif (isset($config['type'])) {
                // 转类型
                $input = $this->convertToType($input, $config['type']);
            }

            //别名转换
            if (isset($config['as'])) {
                $result[$config['as']] = $input;
            } else {
                $result[$key] = $input;
            }
        }
        return $result;
    }

    public function convertToType($value, $type)
    {
        switch ($type) {
            case 'ip':
            case 'int':
            case 'float':
            case 'email':
            case 'url':
            case 'bool':
                $value = filter_var($value, self::VALID_STRINGS[$type]);
                break;
            case 'split':
                $value = explode(',', $value);
                break;
            case 'array':
            case 'json':
                if (is_array($value)) {
                    break;
                }
                $value = json_decode($value, JSON_UNESCAPED_UNICODE);
                break;
            case 'date':
            case 'carbon':
                if (is_numeric($value)) {
                    $value = Carbon::createFromTimestamp($value);
                } else {
                    $value = Carbon::parse($value);
                }

                break;
            case 'ip2long':
                $value = ip2long($value) ?: null;
                break;
        }
        return $value;
    }
}
