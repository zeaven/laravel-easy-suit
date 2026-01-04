<?php

namespace Zeaven\EasySuit\Annotations;

// use Doctrine\Common\Annotations\AnnotationReader;
use Attribute;
use ReflectionMethod;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

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


    public function __construct(private string $tpl, private array $variables = [])
    {
    }

    public function toArray()
    {
        $user = [];
        if (Auth::user()) {
            $user = Auth::user()?->toArray();
        }
        $data = request()->attributes->get('$anno_log', []) + $user + $this->variables;
        $log = Str::replaceMatch($this->tpl, $data);
        return [
            // 'type' => $this->type,
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
