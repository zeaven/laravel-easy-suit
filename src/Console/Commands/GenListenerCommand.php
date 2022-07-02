<?php

namespace Zeaven\EasySuit\Console\Commands;

use Zeaven\EasySuit\Console\Base\BaseGeneratorCommand;

/**
 * 业务逻辑生成命令
 *
 */
class GenListenerCommand extends BaseGeneratorCommand
{
    const ROOT_FOLDER = 'Listeners/';
    const SUFFIX = 'Listener';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:listener {name : 事件名称} {--o|author= : 作者}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成事件文件代码，如 gen:listener home/any ==> App/Listeners/Home/AnyListener';


    /**
     * 控制器模板
     *
     * @return [type] [description]
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/listener.stub';
    }
}
