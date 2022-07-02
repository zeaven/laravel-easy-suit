<?php

namespace Zeaven\EasySuit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Arr;

abstract class BaseRequest extends FormRequest
{
    private $ruleConfigs;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 定义验证规则
     * key, // 默认全局规则
     * key => 1, // 默认全局规则，1为默认值
     * key => ['rule' => 'required', default => 1],
     * key => ['rule' => 'required|*', default => 1], // *号指继承全局规则
     * key => ['rule' => 'required|*|integer', default => 1], // *可调理继承顺序
     * key => ['rule' => 'required', default => 1, type => 'int'], // 转换为int型
     * key => ['rule' => 'required', default => 1, type => 'int', 'as' => 'otherKey'], // 变量名替换为otherKey
     *
     * @return [type] [description]
     */
    abstract protected function rule(): array;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $defaultRules = $this->globalRules();
        $this->ruleConfigs = $this->parseRules($this->rule(), $defaultRules);

        return $this->ruleConfigs
            ->mapWithKeys(fn ($item, $key) => [$key => $item['rule'] ?? ''])
            ->toArray();
    }

    public function getRuleConfig()
    {
        //dd($this->ruleConfigs);
        return $this->ruleConfigs->toArray();
    }

    private function parseRules(array $rules, $defaultRules)
    {
        return collect($rules)->mapWithKeys(
            function ($value, $field) use ($defaultRules) {
                $defRule = $defaultRules[$field] ?? '';
                if (is_numeric($field)) {
                    $field = $value;
                    $defRule = $defaultRules[$field] ?? '';
                    $config = ['rule' => $defRule] + ['default' => null];
                } elseif (!is_array($value)) {
                    $config = ['rule' => $defRule, 'default' => $value];
                } else {
                    if (isset($value['rule'])) {
                        // 继承默认规则
                        if (!empty($defRule)) {
                            $value['rule'] = str_replace('*', $defRule, $value['rule']);
                        }
                    }
                    $config = $value + ['rule' => '', 'default' => null];
                }
                return [$field => $config];
            }
        );
    }

    /**
     * 全局验证规则，如果定义了 $_rules 相同规则 ，将会覆盖全局规则
     * @return [type] [description]
     */
    protected function globalRules()
    {
        return [
            // 'mobile'   => 'required|mobile',
            // 'password' => 'required|min:4',
            // 'name'     => 'string|max:40',
            // 'gender'   => 'integer',
            // 'size'     => 'integer',
            // 'keywords' => 'string',
            // 'id'       => 'integer',
            // 'page'     => 'integer',
            // 'phone'    => 'mobile',
            // 'code'     => 'string',
            // 'type'     => 'integer',
            // 'token'    => 'string',
        ];
    }

    /**
     * 获取验证错误的自定义属性
     * 在错误消息里 :email 将会替换为 email address
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * 获取已定义验证规则的错误消息
     *
     * @return array
     */
    public function messages()
    {
        return [
        ];
    }
}
