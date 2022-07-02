<?php

namespace Zeaven\EasySuit\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class UnitTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '运行 phpunit 测试';

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
        $unit = $this->chooseUnits();

        if (!$unit || strtolower($unit) === 'exit') {
            return $this->warn('退出');
        } else {
            $this->runUnit($unit);
        }

        $this->line('结束');
    }

    private function chooseUnits()
    {
        $dir = base_path('tests');
        $file_iterator = $this->traverseDir($dir);
        $files = [];
        foreach ($file_iterator as $file) {
            $files[] = $file;
        }
        array_unshift($files, 'ALL');
        array_unshift($files, 'Exit');

        $choose_file = $this->choice('选择要执行的测试任务?', $files, false);
        return $choose_file;
    }

    function traverseDir($filedir, $base_path = '')
    {
        empty($base_path) && $base_path = $filedir;
        //打开目录
        $dir = @dir($filedir);
        try {
            //列出目录中的文件
            while (($file = $dir->read()) !== false) {
                if (in_array($file, ['.', '..', 'TestCase.php', 'CreatesApplication.php'])) {
                    continue;
                } elseif (is_dir($filedir . "/" . $file)) {
                    //递归遍历子目录
                    yield from $this->traverseDir($filedir . "/" . $file, $base_path);
                } elseif (\Str::endsWith($file, '.php')) {
                    //输出文件完整路径
                    yield trim(str_replace($base_path, '', $filedir)  . "/" . $file, '/');
                }
            }
        } finally {
            $dir && $dir->close();
        }
    }

    private function runUnit($unit)
    {
        $path = ['./vendor/bin/phpunit'];
        if (strtoupper($unit) !== 'ALL') {
            $path[] = ' tests/' . $unit;
        }
        $this->comment('执行命令：' . implode(' ', $path));
        $process = new Process($path);
        $process->setTimeout(60);
        $process->setIdleTimeout(60);
        $process->setWorkingDirectory(base_path());
        $process->start();
        $iterator = $process->getIterator($process::ITER_SKIP_ERR | $process::ITER_KEEP_OUTPUT);
        foreach ($iterator as $data) {
            $this->line($data);
        }
    }
}
