<?php

/**
 * postman 回退操作
 *
 * @date    2020-06-05 13:38:12
 * @version $Id$
 */

namespace Zeaven\EasySuit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Storage;

class PostmanRevert extends Command
{
    protected $signature = 'pm:revert';
    protected $description = '删除上次从Postman生成的代码和文件';

    const POSTMAN_GENERATOR_BACKUP = 'postman/backup.json';


    public function handle()
    {
        if (!Storage::exists(static::POSTMAN_GENERATOR_BACKUP)) {
            return $this->error('无删除数据！');
        }
        $data = Storage::get(static::POSTMAN_GENERATOR_BACKUP);
        $mapper = collect(json_decode($data, true));


        $this->revert($mapper);
        Storage::delete(static::POSTMAN_GENERATOR_BACKUP);
    }

    private function revert(Collection $mapper)
    {
        $mapper->each(
            function ($item, $key) {
                if (boolval($item['created'])) {
                    // 删除文件
                    $this->info('删除控制器：' . $key);
                    $this->deleteFile($item['file']);
                    $this->deleteFile($item['logic']);
                    $this->deleteRequest($item['request']);
                } else {
                    // 删除请求对象
                    $this->deleteRequest($item['request']);
                    // 删除方法
                    $this->info('移除控制器：' . $key . '中的方法');
                    $this->deleteFunc($item['file'], $item['func']);
                }
            }
        );
    }

    private function deleteFile(string $file)
    {
        throw_on(!unlink($file), '删除文件失败：' . $file);
    }

    private function deleteFunc(string $file, array $func)
    {
        if (!file_exists($file)) {
            return;
        }
        $content = file_get_contents($file);

        foreach ($func as $func_text) {
            $func_text = str_replace('/** #generate function# 删除后将无法自动生成控制器方法 */', '', $func_text);
            $content = str_replace($func_text, '', $content);
        }

        file_put_contents($file, $content);
    }

    private function deleteRequest(array $requests)
    {
        foreach ($requests as $request) {
            throw_on(!unlink($request), '删除请求对象失败：' . $request);
        }
    }
}
