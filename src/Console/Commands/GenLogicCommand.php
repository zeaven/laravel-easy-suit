<?php

namespace Zeaven\EasySuit\Console\Commands;

use Zeaven\EasySuit\Console\Base\BaseGeneratorCommand;

/**
 * 业务逻辑生成命令
 *
 */
class GenLogicCommand extends BaseGeneratorCommand
{
    const ROOT_FOLDER = 'Logics/';
    const SUFFIX = 'Logic';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:logic {name : 业务逻辑名} {--o|author= : 作者}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成业务逻辑文件代码，如 gen:logic home/any ==> App/Logics/Home/AnyLogic';


    /**
     * 控制器模板
     *
     * @return [type] [description]
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/logic.stub';
    }
}
