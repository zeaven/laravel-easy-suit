<?php

namespace Zeaven\EasySuit\Console\Commands;

use Zeaven\EasySuit\Console\Base\BaseGeneratorCommand;

/**
 * 业务逻辑生成命令
 *
 */
class GenMapperCommand extends BaseGeneratorCommand
{
    const ROOT_FOLDER = 'Http/ResponseMappers/';
    const SUFFIX = 'Mapper';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:mapper {name : 名称} {--o|author= : 作者}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成资源映射文件代码，如 gen:mapper home/any ==> App/Http/ResponseMappers/Home/AnyMapper';


    /**
     * 控制器模板
     *
     * @return [type] [description]
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/mapper.stub';
    }
}
