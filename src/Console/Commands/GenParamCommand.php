<?php

namespace Zeaven\EasySuit\Console\Commands;

use Zeaven\EasySuit\Console\Base\BaseGeneratorCommand;

/**
 * 业务逻辑生成命令
 *
 */
class GenParamCommand extends BaseGeneratorCommand
{
    const ROOT_FOLDER = 'Domain/Module/?/Param/?';
    const SUFFIX = 'Param';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:param {name : 实体名} {--o|author= : 作者}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成领域参数文件代码，如 gen:param home/any ==> App/Domain/Module/Home/Param/AnyParam';


    /**
     * 控制器模板
     *
     * @return [type] [description]
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/param.stub';
    }
}
