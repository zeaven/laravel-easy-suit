<?php

namespace Zeaven\EasySuit\Console\Commands;

use Zeaven\EasySuit\Console\Base\BaseGeneratorCommand;

/**
 * 业务逻辑生成命令
 *
 */
class GenContextCommand extends BaseGeneratorCommand
{
    const ROOT_FOLDER = 'Domain/Module/?/Context/?';
    const SUFFIX = 'Context';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:ctx {name : 名称} {--o|author= : 作者}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成领域上下文文件代码，如 gen:ctx home/any ==> App/Domain/Module/Home/Context/AnyContext';


    /**
     * 控制器模板
     *
     * @return [type] [description]
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/ctx.stub';
    }
}
