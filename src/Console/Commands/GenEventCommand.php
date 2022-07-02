<?php

namespace Zeaven\EasySuit\Console\Commands;

use Zeaven\EasySuit\Console\Base\BaseGeneratorCommand;

/**
 * 业务逻辑生成命令
 *
 */
class GenEventCommand extends BaseGeneratorCommand
{
    const ROOT_FOLDER = 'Domain/Events/';
    const SUFFIX = 'Event';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:event {name : 事件名} {--o|author= : 作者}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成领域事件文件代码，如 gen:event home/any ==> App/Domain/Events/Home/AnyEvent';


    /**
     * 控制器模板
     *
     * @return [type] [description]
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/event.stub';
    }
}
