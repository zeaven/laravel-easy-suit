<?php

namespace Zeaven\EasySuit\Console\Commands;

use Zeaven\EasySuit\Console\Base\BaseGeneratorCommand;

/**
 * 业务逻辑生成命令
 *
 */
class GenRequestCommand extends BaseGeneratorCommand
{
    const ROOT_FOLDER = 'Http/Requests/';
    const SUFFIX = 'Request';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:request {name : 请求对象名} {--o|author= : 作者}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成请求对象文件代码，如 gen:request home/any ==> App/Http/Requests/Home/AnyRequest';


    /**
     * 控制器模板
     *
     * @return [type] [description]
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/request.stub';
    }

    protected function replaceCustom(string $stub, string $name)
    {
        $baseRequest = 'BaseRequest';
        $isApi = preg_match('/Api\\\\/', $name);
        if ($isApi) {
            $baseRequest = 'ApiRequest';
        } else {
            $baseRequest = 'AdminRequest';
        }

        $stub = str_replace('{{BaseRequest}}', $baseRequest, $stub);
        return $stub;
    }
}
