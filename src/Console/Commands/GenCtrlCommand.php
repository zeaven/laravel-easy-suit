<?php

namespace Zeaven\EasySuit\Console\Commands;

use Zeaven\EasySuit\Console\Base\BaseGeneratorCommand;
use Str;
use Artisan;

/**
 * 控制器生成命令
 *
 */
class GenCtrlCommand extends BaseGeneratorCommand
{
    const ROOT_FOLDER = 'Http/Controllers/';

    const SUFFIX = 'Controller';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:ctrl {name : 控制器名}  {--o|author= : 作者}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成控制器文件代码，如 gen:ctrl home/any ==> App/Http/Controllers/Home/AnyController';


    /**
     * 控制器模板
     *
     * @return [type] [description]
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/ctrl.stub';
    }

    protected function replaceCustom(string $stub, string $name)
    {
        $logic_name = Str::studly(str_replace('Controller', '', Str::after($name, 'App\\Http\\Controllers\\')));
        Artisan::call(
            'gen:logic',
            [
            'name' => $logic_name,
            '--force' => true
            ]
        );
        $logic_namespace = 'use App\\Logics\\' . $logic_name . 'Logic;';
        $is_admin_ctrl = preg_match('/Admin\\\\/', $logic_name);
        if ($is_admin_ctrl) {
            $request = 'use App\Http\Requests\AdminRequest;';
        } else {
            $request = 'use App\Http\Requests\ApiRequest;';
        }

        $stub = str_replace('{{logic_namespace}}', $logic_namespace, $stub);
        $stub = str_replace('{{logic}}', class_basename($logic_name . 'Logic'), $stub);
        $stub = str_replace('{{request}}', $request, $stub);
        return $stub;
    }
}
