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

class PostmanClear extends Command
{
    protected $signature = 'pm:clear';
    protected $description = '删除Postman缓存';

    const POSTMAN_GENERATOR_CACHE = 'postman/cache.json';


    public function handle()
    {
        if (Storage::exists(static::POSTMAN_GENERATOR_CACHE)) {
            Storage::delete(static::POSTMAN_GENERATOR_CACHE);
        }
        $this->info('删除完成！');
    }
}
