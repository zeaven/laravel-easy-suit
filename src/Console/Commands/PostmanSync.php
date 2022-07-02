<?php

namespace Zeaven\EasySuit\Console\Commands;

use Zeaven\EasySuit\Services\PostmanClient;
use Artisan;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PostmanSync extends Command
{
    private $_client;

    private $_files = [];

    private $_force = false;

    private $_mapper = [];

    private $_route = [];

    const POSTMAN_GENERATOR_BACKUP = 'postman/backup.json';
    const POSTMAN_GENERATOR_CACHE = 'postman/cache.json';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pm:run {collection? : 生成代码的集合名称} {--F|force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从postman接口定义生成Laravel控制器代码';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->_client = app(PostmanClient::class);
        $collection_name = $this->argument('collection');
        $this->_force = $this->option('force');

        $folders = $this->getCollection($collection_name);
        $selected_folders = $this->choiceFolders($folders);
        try {
            $this->handleFolders($selected_folders);

            foreach ($this->_files as ['path' => $path, 'content' => $content, 'logic_path' => $logic_path, 'logic_content' => $logic_content]) {
                file_put_contents($path, $content);
                file_put_contents($logic_path, $logic_content);
            }
            $this->mergeRouteConfig();
        } catch (\Exception $ex) {
            $this->error($ex);
        } finally {
            // 保存创建记录，提供删除能力
            \Storage::put(static::POSTMAN_GENERATOR_BACKUP, json_encode($this->_mapper, JSON_UNESCAPED_UNICODE));
        }
    }

    protected function choiceFolders(array $folders, $parent = null)
    {
        // 只处理目录
        $fds = $parent ? ['生成代码'] : [];
        foreach ($folders as $folder) {
            if (isset($folder['request'])) {
                continue;
            }
            $fds[] = $folder['name'];
        }

        $selected = $this->choice(
            '选择要生成控制器的目录?' . ($parent ? "[{$parent['name']}]" : ''),
            $fds,
            0,
            $maxAttempts = null,
            $allowMultipleSelections = false
        );

        if ($selected === '生成代码') {
            return [$parent];
        }

        foreach ($folders as $folder) {
            if ($folder['name'] === $selected) {
                $selected = $folder;
            }
        }
        // 选择下级目录
        $sub_folders = [];
        foreach ($selected['item'] as $value) {
            if (!isset($value['request'])) {
                $sub_folders[] = $value;
            }
        }
        if (!empty($sub_folders)) {
            return $this->choiceFolders($sub_folders, $selected);
        }

        return [$selected];
    }

    protected function handleFolders($selected_folders, $namespaces = [])
    {
        foreach ($selected_folders as $folder) {
            $namespace = $namespaces;
            if (!isset($folder['request'])) {
                $this->handleFolders($folder['item'], $namespace);
            } else {
                $this->generateApi($folder['name'], $folder['request'], $folder['description'] ?? '');
            }
        }
    }

    /**
     * 生成控制器
     *
     * @param  [type] $name [description]
     * @param  [type] $request        [description]
     * @return [type]             [description]
     */
    protected function generateApi(string $name, array $request, string $description)
    {
        $method = strtolower($request['method']);
        // $query => [{key, value, type}], $urlPath => ['api', 'login', 'dologin']
        ['raw' => $url, 'path' => $urlPaths] = $request['url'];
        $query = $request['url']['query'] ?? [];
        if ($method === 'post' || $method === 'put') {
            $body = Arr::get($request, 'body.' . Arr::get($request, 'body.mode', 'formdata'), []);
            $query = array_merge($query, $body);
        }
        $api = $this->parseController($method, $url, $query, $urlPaths);
        $ctrl = $api['ctrl'];
        ['path' => $path, 'content' => $ctrl_stub, 'logic_path' => $logic_path, 'logic_content' => $logic_stub] = $this->createController($ctrl);
        $request = $this->createRequest($api['action'], $ctrl, $api['query']);
        $method_desc = $request['description'] ?? '';
        [$ctrl_text, $func_stub, $logic_text] = $this->createFunction(
            $name,
            $ctrl_stub,
            $logic_stub,
            $api['action'],
            $api['query'],
            $api['params'],
            $method_desc,
            class_basename($request)
        );
        $this->addMapperRoute($api, $url, $method, $name);
        if ($ctrl_text !== false) {
            $this->addMapperFunc($ctrl, $func_stub);

            $this->_files[$ctrl]['content'] = $ctrl_text;
            $this->_files[$ctrl]['logic_content'] = $logic_text;
            $this->info("请求方法：[{$api['action']}]创建成功！");
        }
    }

    /**
     * 解释控制器
     *
     * restful接口规定
     * get      /api/articles               文章列表，方法index
     * post     /api/articles               创建文章, 方法add
     * get      /api/articles/{article_id}  获取文章信息，方法get
     * put      /api/articles/{article_id}  更新文章, 方法update
     * delete   /api/articles/{article_id}  删除文章，方法delete
     * 其他方法
     * get      /api/articles/{article_id}/category 获取文章分类，方法 category
     * put      /api/articles/{article_id}/category 修改文章分类，方法 updateCategory
     *
     * @param  string $url      [description]
     * @param  array  $query   [description]
     * @param  array  $urlPaths [description]
     * @return object           {ctrl, action, query, params}
     */
    protected function parseController(string $method, string $url, array $query, array $urlPaths): array
    {
        // 判断是否restful接口，restful接口的控制器为复数形式或s结尾
        $params = []; // url路径参数
        $namespaces = [];
        $action = '';
        for ($i = 0,$l = count($urlPaths); $i < $l; $i++) {
            $path = $urlPaths[$i];
            if (Str::contains($path, '{')) {
                // 参数
                $params[] = preg_replace('/[{}]/', '', $path);
            } else {
                $action = Str::camel(str_replace('-', '_', $path)); // 方法名为驼峰式
                $namespaces[] = Str::studly($action);    // 命名空间为变种驼峰命名
            }
        }
        // 获取倒数第二个参数，判断是否restful接口
        $ctrl = head(array_slice($namespaces, -2, 1));
        $restful = $ctrl === Str::plural($ctrl);
        // 如果方法名是复数形式，说明是result接口
        if (($restful && $action !== Str::plural($action)) || $action === Str::plural($action)) {
            // restful 接口以请求method为方法名
            $action_key = ($method === 'get' && count($params) === 0) ? 'index' : $method;
            $action = [
            'index' => 'index',
            'get' => 'show',
            'post' => 'store',
            'put' => 'update',
            'delete' => 'destroy'
            ][$action_key] ?? $action_key;

            if ($restful) {
                // 最后一个是方法名，需要去掉
                $act_name = array_pop($namespaces);
                if (!in_array($action, ['index', 'show'])) {
                    $action .= $act_name;
                } else {
                    $action = Str::camel($act_name);
                }
            }
        } else {
            // 最后一个是方法名，需要去掉
            array_pop($namespaces);
        }
        // 命名空间最后一个是控制器名，转成单数形式
        $namespaces[] = Str::singular(array_pop($namespaces));
        if (count($namespaces) <= 2) {
            $namespaces[] = last($namespaces);
        }
        // 合并控制器路径
        $ctrl = implode('/', $namespaces);

        return [
            'ctrl' => $ctrl,
            'action' => $action,
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * 创建控制器
     * @param  [type] $ctrl [description]
     * @return [type]       [description]
     */
    protected function createController(string $ctrl): array
    {
        if (isset($this->_files[$ctrl])) {
            return $this->_files[$ctrl];
        }
        $force = $this->_force;
        $ctrl_path = $ctrl . 'Controller.php';
        $file_path = app_path('Http/Controllers/' . $ctrl_path);
        $logic_file_path = app_path('Logics/' . $ctrl . 'Logic.php');
        if ($force || !file_exists($file_path)) {
            // 添加文件映射，失败后可通过pm:revert回复
            $this->addMapperFile($ctrl, $file_path, true);
            $this->addMapperLogic($ctrl, $logic_file_path);
            Artisan::call(
                'gen:ctrl',
                [
                'name' => $ctrl,
                '--force' => true
                ]
            );
            $this->info("控制器：[{$ctrl_path}]创建成功！");
            $this->info("业务逻辑：[{$ctrl}Logic.php]创建成功！");
        } else {
            $this->addMapperFile($ctrl, $file_path, false);
            $this->comment("控制器：[{$ctrl_path}]已存在！");
            $this->comment("业务逻辑：[{$ctrl}Logic.php]已存在！");
        }

        $this->_files[$ctrl] = ['path' => $file_path, 'content' => file_get_contents($file_path),
            'logic_path' => $logic_file_path, 'logic_content' => file_get_contents($logic_file_path)];

        return $this->_files[$ctrl];
    }

    /**
     * 创建控制请求对象
     * @param  string $ctrl  [description]
     * @param  array  $query [description]
     * @return [type]        [description]
     */
    protected function createRequest(string $method, string $ctrl, array $query): string
    {
        if (count($query) === 0) {
            return preg_match('/Admin\//', $ctrl) ?
                'Admin' :
                'Api';
        }
        $ctrl = collect(explode('/', $ctrl))->unique()->implode('/');
        // 生成Request
        $request = $ctrl . '/' . Str::studly($method);
        $request_path = str_replace(
            '-',
            '_',
            Str::singular($request)
        ) . 'Request.php';
        $force = $this->_force;
        $file_path = app_path('Http/Requests/' . $request_path);
        if ($force || !file_exists($file_path)) {
            // 添加文件映射，失败后可通过pm:revert回复
            $this->addMapperRequest($ctrl, $file_path, true);
            Artisan::call(
                'gen:request',
                [
                'name' => $request,
                '--force' => true
                ]
            );
        } else {
            $this->comment("请求对象：[{$request_path}]已存在！");
            return $request;
        }

        $content = file_get_contents($file_path);
        // 规则param信息
        $param = 'return [' . PHP_EOL;
        $param .= collect($query)->unique('key')->map(
            function ($item) {
                $desc = $item['description'] ?? '';
                return '            ' . "// {$desc}" . PHP_EOL . '            ' . "'{$item['key']}'";
            }
        )->join(',' . PHP_EOL);
        $param .= PHP_EOL . '        ];' . PHP_EOL;
        $content = str_replace('{{params}}', $param, $content);
        file_put_contents($file_path, $content);

        $this->info("请求对象：[{$request_path}]创建成功！");

        return $request;
    }

    /**
     * 生成控制器方法
     * @param  string  $name [description]
     * @param  string  $ctrl_stub [description]
     * @param  string  $logic_stub [description]
     * @param  string $action     [description]
     * @param  array  $query      [description]
     * @param  array  $params     [description]
     * @return [type]             [description]
     */
    protected function createFunction(string $name, string $ctrl_stub, string $logic_stub, string $action, array $query, array $params, string $description, string $request)
    {
        // 判断方法是否存在
        if (preg_match("/\s{$action}\s*\(/", $ctrl_stub)) {
            $this->comment("请求方法[{$action}]已存在");
            return [false, null];
        }
        // 查找方法勾子
        if (!preg_match('/\/\*\* \#generate function\#[^\*]+\*\//', $ctrl_stub, $match)) {
            $this->error("请求方法[{$action}]生成失败，#generate function#不存在");
            return [false, null];
        }
        $hook = $match[0];
        $request .= 'Request';
        $func_stub = file_get_contents(__DIR__ . '/stubs/func.stub');
        $func_stub = str_replace('{{name}}', $name, $func_stub);
        $func_stub = str_replace('{{comment}}', $description, $func_stub);
        $func_stub = str_replace('{{time}}', date('Y-m-d H:i:s'), $func_stub);
        $func_stub = str_replace('{{action}}', $action, $func_stub);
        $func_stub = str_replace('{{request}}', $request, $func_stub);

        if (count($params)) {
            $param = ', int $' . implode(', int $', $params);
        } else {
            $param = '';
        }
        $func_stub = str_replace('{{params}}', $param, $func_stub);
        if (count($query)) {
            $query = array_column($query, 'key');
            // $args = "\$param = \$request->params();  // params方法传入对应的Param对象" . PHP_EOL;
            $args = '// 请求传入的参数值，要获取key/value参数数组请使用 $param = $request->params();' . PHP_EOL;
            $args .= "\t\t" . '[$' . implode(', $', $query) . '] = ' . (count($query) > 6 ? PHP_EOL : '');
            $args .= (count($query) > 6 ? "\t\t\t" : '') . "\$request->values();";
        } else {
            $args = '';
        }
        $func_stub = str_replace('{{query}}', $args, $func_stub);

        $ctrl_stub = str_replace($hook, $func_stub, $ctrl_stub);

        $logic_stub = $this->createLogicFunction($name, $logic_stub, $action, $description);

        return [$ctrl_stub, $func_stub, $logic_stub];
    }

    protected function createLogicFunction(string $name, string $logic_stub, string $action, string $description)
    {
        // 判断方法是否存在
        if (preg_match("/\s{$action}\s*\(/", $logic_stub)) {
            $this->comment("请求方法[{$action}]已存在");
            return [false, null];
        }
        // 查找方法勾子
        if (!preg_match('/\/\*\* \#generate function\#[^\*]+\*\//', $logic_stub, $match)) {
            $this->error("请求方法[{$action}]生成失败，#generate function#不存在");
            return [false, null];
        }
        $hook = $match[0];
        $func_stub = file_get_contents(__DIR__ . '/stubs/logic_func.stub');
        $func_stub = str_replace('{{name}}', $name, $func_stub);
        $func_stub = str_replace('{{comment}}', $description, $func_stub);
        $func_stub = str_replace('{{time}}', date('Y-m-d H:i:s'), $func_stub);
        $func_stub = str_replace('{{action}}', $action, $func_stub);

        $logic_stub = str_replace($hook, $func_stub, $logic_stub);

        return $logic_stub;
    }

    private function cacheCollections()
    {
        $response = $this->_client->get('collections');
        $collections = collect($response['collections'])
            ->mapWithKeys(
                function ($item) {
                    return [$item['name'] => Arr::only($item, ['id','uid'])];
                }
            )->toArray();
        \Storage::put(static::POSTMAN_GENERATOR_CACHE, json_encode($collections, JSON_UNESCAPED_UNICODE));
        return $collections;
    }

    /**
     * 获取集合
     * @param  string $name [description]
     * @return [type]       [description]
     */
    private function getCollection(?string $name)
    {
        // 先从本地获取
        $collects = [];
        if (\Storage::exists(static::POSTMAN_GENERATOR_CACHE)) {
            $collects = json_decode(\Storage::get(static::POSTMAN_GENERATOR_CACHE), true);
        }
        if (!isset($collects[$name])) {
            $collects = $this->cacheCollections();
        }

        if ($name) {
            throw_on(!isset($collects[$name]), '集合不存在');
        } else {
            $name = $this->choice(
                '选择Postman集合?',
                array_keys($collects),
                0,
                null,
                false
            );
        }
        $uid = $collects[$name]['uid'];

        $response = $this->_client->get('collections/' . $uid);

        return $response['collection']['item'];
    }

    private function addMapperFile(string $ctrl, string $file_path, bool $created = false)
    {
        if ($this->addMapper('file', $ctrl, $file_path)) {
            $this->_mapper[$ctrl]['created'] = $created;
        }
    }

    private function addMapperLogic(string $ctrl, string $file_path)
    {
        $this->addMapper('logic', $ctrl, $file_path);
    }

    private function addMapperFunc(string $ctrl, string $func)
    {
        $this->addMapper('func', $ctrl, $func);
    }

    private function addMapperRequest(string $ctrl, string $request_path)
    {
        $this->addMapper('request', $ctrl, $request_path);
    }

    private function addMapperRoute(array $api, string $url, string $method, string $desc = '')
    {
        $url = str($url)->after('/')->before('?')->replace('{{', '{')->replace('}}', '}')->value();  // api/test/home/{uid}
        $segments = str($api['ctrl'])->explode('/')->toArray();
        $group = strtolower(array_shift($segments));    // api => routes/api.php
        $root = 'index';
        $namespaces = [];
        $ctrl = last($segments) . 'Controller';
        $action = $api['action'];
        if (count($segments) > 1) {
            $root = str($segments[0])->snake()->value();
            $namespaces = array_slice($segments, 0, -1);
        }
        $name = "{$group}_{$root}_routes";  // => api_test_routes

        $this->_route[] = compact('group', 'name', 'url', 'root', 'namespaces', 'ctrl', 'action', 'method', 'desc');
    }

    private function mergeRouteConfig()
    {
        foreach ($this->_route as $route) {
            $this->createRouteGroup($route);
        }
    }

    private function createRouteGroup($route)
    {
        $routeConfigPath = base_path("routes/{$route['group']}.php");
        if (!file_exists($routeConfigPath)) {
            return;
        }
        $routeContent = file_get_contents($routeConfigPath);
        if (!preg_match("/\/\*+\s?generate config\s?\*+\//", $routeContent)) {
            $routeContent .= <<<TXT

/******* generate config ********/

TXT;
        }
        if (!preg_match("/\/\*+\s?generate router\s?\*+\//", $routeContent)) {
            $routeContent .= <<<TXT

/******* generate router ********/
TXT;
        }
        $urlPath = str($route['url'])->after('/')->value();
        $method = $route['method'];
        $pregUrl = "{$method}\('" . str_replace('/', '\/', $urlPath);
        if (preg_match("/{$pregUrl}/", $routeContent)) {
            return;
        }
        $name = $route['name'];
        $ctrl = implode('\\', array_merge($route['namespaces'], [$route['ctrl']]));
        if (!preg_match("/{$name}/", $routeContent)) {
            // 创建路由
            if (!preg_match("/\/\*+\s?generate config\s?\*+\//", $routeContent, $hookMatch)) {
                $this->error("路由配置，# generate config #不存在");
                return;
            }
            $hook = $hookMatch[0];
            $plainText = <<<TXT

/*** {$name} start ***/
if (! function_exists('{$name}')) {
    function {$name}()
    {
    }/*** {$name} end ***/
}

/******* generate config ********/
TXT;
            $routeContent = str_replace($hook, $plainText, $routeContent);
            if (preg_match("/\/\*+\s?generate router\s?\*+\//", $routeContent, $loaderMatch)) {
                $hook = $loaderMatch[0];
                $loaderStr = <<<LOAD
{$name}();

{$hook}
LOAD;
                $routeContent = str_replace($hook, $loaderStr, $routeContent);
            }
        }
        // 获取路由插入点
        if (!preg_match("/}\/\*+\s?{$name} end\s?\*+\//", $routeContent, $importMatch)) {
            $this->error("路由配置，插入路由{$route['url']}失败!");
            return;
        }
        $importHook = $importMatch[0];
        $routeStr = <<<STR
    // {$route['desc']}
        Route::{$method}('{$urlPath}', "{$ctrl}@{$route['action']}");
    }/*** {$name} end ***/
STR;

        $routeContent = str_replace($importHook, $routeStr, $routeContent);

        file_put_contents($routeConfigPath, $routeContent);
    }

    private function addMapper(string $type, string $ctrl, string $content)
    {
        $mapper = $this->_mapper[$ctrl] ?? [
            'created' => false,
            'file' => '',
            'logic' => '',
            'func' => [],
            'request' => []
        ];
        if ($type === 'file' && empty($mapper['file'])) {
            $mapper['file'] = $content;
        } elseif ($type === 'logic') {
            $mapper['logic'] = $content;
        } elseif ($type === 'func') {
            if (!in_array($content, $mapper['func'])) {
                $mapper['func'][] = $content;
            }
        } elseif ($type === 'request') {
            $mapper['request'][] = $content;
        } else {
            return false;
        }
        $this->_mapper[$ctrl] = $mapper;
        return true;
    }
}
