<?php

namespace Zeaven\EasySuit\Console\Commands;

use Zeaven\EasySuit\Console\Base\BaseGeneratorCommand;

/**
 * 业务逻辑生成命令
 *
 */
class GenModelCommand extends BaseGeneratorCommand
{
    const ROOT_FOLDER = 'Domain/Module/?/Model/?';
    const SUFFIX = '';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:model {name : 模型名} {--o|author= : 作者}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成领域模型文件代码，如 gen:model home/any ==> App/Domain/Module/Home/Model/AnyModel';


    /**
     * 控制器模板
     *
     * @return [type] [description]
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/model.stub';
    }
}
