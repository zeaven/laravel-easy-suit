<?php

namespace Zeaven\EasySuit\Console\Traits;

use Str;

/**
 *
 *
 * @date    2020-06-02 15:33:57
 * @version $Id$
 */
trait getNameInputTrait
{
    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        $name = trim($this->argument('name'));
        if (Str::contains(static::ROOT_FOLDER, '?')) {
            $name = Str::replaceArray('?', explode('/', $name), static::ROOT_FOLDER);
        } else {
            $name = static::ROOT_FOLDER . $name;
        }

        if (!Str::endsWith($name, static::SUFFIX)) {
            $name = $name . static::SUFFIX;
        }
        $arr = preg_split('/(\/|\\\\)/', $name);
        $name = implode(
            '/',
            array_map(
                fn($str) => Str::studly($str),
                $arr
            )
        );

        return $name;
    }
}
