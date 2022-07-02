<?php

namespace Zeaven\EasySuit\Console\Commands;

use Zeaven\EasySuit\Console\Base\BaseGeneratorCommand;
use Str;

/**
 * 业务逻辑生成命令
 *
 */
class GenServiceCommand extends BaseGeneratorCommand
{
    const ROOT_FOLDER = 'Domain/Module/?/Service/?';
    const SUFFIX = 'Service';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:service {name : 实体名} {--o|author= : 作者}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成领域服务文件代码，如 gen:service home/any ==> App/Domain/Module/Home/Service/AnyService';


    /**
     * 控制器模板
     *
     * @return [type] [description]
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/service.stub';
    }

    protected function replaceCustom(string $stub, string $name)
    {
        $model_class = str_replace('\\Service\\', '\\Context\\', $name);
        $model_class = str_replace('Service', 'Context', $model_class);
        $stub = str_replace('{{ctx_class}}', $model_class, $stub);
        $model = Str::afterLast($model_class, '\\');
        return str_replace('{{ctx}}', $model, $stub);
    }
}
