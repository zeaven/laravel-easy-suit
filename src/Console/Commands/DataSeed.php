<?php

/**
 * 数据填充 db:seed 交互命令
 *
 * @date    2018-07-05 21:10:09
 * @version $Id$
 */

namespace Zeaven\EasySuit\Console\Commands;

use Artisan;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Str;

class DataSeed extends Command
{
    protected $signature = 'data-seed';// {seeder=SaasTableSeeder}
    /**
     * 控制台命令说明。
     *
     * @var string
     */
    protected $description = 'run a data seeder';

    protected $seeder_path;
    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $this->seeder_path = base_path('database/seeders');

        $files = $this->getSeeders();

        $seeder_file = $this->chooseSeeder($files);
        if (strtolower($seeder_file) === 'exit') {
            $this->line('退出执行。');
            return;
        } else {
            $this->line('开始执行：' . $seeder_file);
        }

        Artisan::call('db:seed', [
                '--class' => $seeder_file
            ], $this->output);
        $this->line('执行完毕。');
    }

    private function getSeeders()
    {
        $dir = dir($this->seeder_path);
        try {
            while (($file = $dir->read()) !== false) {
                if (!Str::endsWith($file, '.php')) {
                    continue;
                }
                $filename = pathinfo($file)['filename'];
                $clsz = "Database\\Seeders\\{$filename}";
                $desc = data_get(new $clsz(), 'description', '');
                $filename .= '   --' . $desc;
                $files[] = $filename;
            }
            sort($files);
            array_unshift($files, 'Exit');
        } finally {
            $dir->close();
        }

        return $files;
    }

    private function chooseSeeder(array $seeder_files)
    {
        $choose_file = $this->choice('选择要执行的Seeder?', $seeder_files, false);
        $choose_file = trim(explode('--', $choose_file)[0]);
        return $choose_file;
    }
}
