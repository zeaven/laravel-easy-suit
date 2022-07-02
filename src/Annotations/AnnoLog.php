<?php

namespace Zeaven\EasySuit\Annotations;

// use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionMethod;
use Arr;
use Attribute;
use Str;

/**
 * 用户日志注解
 *
 * @date    2020-06-30 10:33:33
 * @version $Id$
 * @Annotation
 * @Target({"METHOD"})
 */
#[Attribute(Attribute::TARGET_METHOD)]
class AnnoLog
{
    /**
     * @Required()
     * 1管理员，2投资者用户，3居间商，4系统生成
     * @var int
     */
    // private int $type;
    /**
     * @Required()
     *
     * @var string
     */
    // private string $tpl;

    public function __construct(private int $type, private string $tpl)
    {
    }

    public function toArray()
    {
        $user = [];
        if (auth()->user()) {
            $user = auth()->user()->toArray();
        }
        $variables = request()->attributes->get('$anno_log', []) + $user;
        $log = Str::replaceMatch($this->tpl, $variables);
        return [
            'type' => $this->type,
            'log' => $log,
        ];
    }

    public static function data($key, $value = null)
    {
        if (!is_array($key)) {
            $key = [$key => $value];
        }
        $data = request()->attributes->get('$anno_log', []);
        $data = array_merge($data, $key);
        request()->attributes->set('$anno_log', $data);
    }

    public static function annotation(string $action)
    {
        [$ctrl, $method] = explode('@', $action);
        $rm = new ReflectionMethod($ctrl, $method);
        // $reader = new AnnotationReader();

        // $annotation = $reader->getMethodAnnotation($rm, self::class);
        $attrs = $rm->getAttributes(self::class);

        if (count($attrs)) {
            return $attrs[0]->newInstance()->toArray();
        }
    }
}
