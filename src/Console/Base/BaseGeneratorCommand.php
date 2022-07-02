<?php

namespace Zeaven\EasySuit\Console\Base;

use Zeaven\EasySuit\Console\Traits\getNameInputTrait;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;

/**
 * 生成器基类
 *
 * @date    2020-06-02 15:46:53
 * @version $Id$
 */
abstract class BaseGeneratorCommand extends GeneratorCommand
{
    use getNameInputTrait;

    /**
     * Create a new controller creator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct($files);
        $def = $this->getDefinition();
        if (!$def->hasOption('force')) {
            $forceOption = new InputOption('force', 'f', null, '强制生成');
            $def->addOption($forceOption);
        }
    }

    public function handle()
    {
        $name = $this->qualifyClass($this->getNameInput());

        $path = $this->getPath($name);

        // First we will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if (
            (! $this->hasOption('force') ||
             ! $this->option('force')) &&
             $this->alreadyExists($this->getNameInput())
        ) {
            $this->error($this->type . ' already exists!');

            return false;
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $this->makeDirectory($path);

        $this->files->put(
            $path,
            $this->sortImports(
                $this->replaceCustom(
                    $this->replaceComment(
                        $this->buildClass($name)
                    ),
                    $name
                )
            )
        );

        $this->info($this->type . ' created successfully.');
    }

    protected function replaceCustom(string $stub, string $name)
    {
        return $stub;
    }

    /**
     * 替换头部注释
     *
     * @param  string $stub [description]
     * @return [type]       [description]
     */
    protected function replaceComment(string $stub)
    {
        $comment = $this->files->get(__DIR__ . '/../Commands/stubs/comment.stub');
        $author = $this->getAuthorInput();
        $comment = str_replace('{{author}}', $author, $comment);
        $comment = str_replace('{{date}}', date('Y-m-d H:i:s'), $comment);

        return str_replace(['DummyComment', '{{ comment }}', '{{comment}}'], $comment, $stub);
    }

    /**
     * 返回注释作者
     *
     * @return [type] [description]
     */
    protected function getAuthorInput()
    {
        if ($this->hasOption('author')) {
            return $this->option('author') ?? 'generator';
        } else {
            return 'generator';
        }
    }
}
