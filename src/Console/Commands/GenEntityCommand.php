<?php

namespace Zeaven\EasySuit\Console\Commands;

use Zeaven\EasySuit\Console\Base\BaseGeneratorCommand;
use Str;

/**
 * 业务逻辑生成命令
 *
 */
class GenEntityCommand extends BaseGeneratorCommand
{
    const ROOT_FOLDER = 'Domain/Module/?/Entity/?';
    const SUFFIX = 'Entity';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:entity {name : 实体名} {--o|author= : 作者}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成领域实体文件代码，如 gen:model home/any ==> App/Domain/Module/Home/Entity/AnyEntity';


    /**
     * 控制器模板
     *
     * @return [type] [description]
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/entity.stub';
    }

    protected function replaceCustom(string $stub, string $name)
    {
        $model_class = str_replace('\\Entity\\', '\\Model\\', $name);
        $model_class = str_replace('Entity', '', $model_class);
        $stub = str_replace('{{model_class}}', $model_class, $stub);
        $model = Str::afterLast($model_class, '\\');
        return str_replace('{{model}}', $model, $stub);
    }
}
