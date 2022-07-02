<?php

namespace Zeaven\EasySuit\Validators;

use Zeaven\EasySuit\Validators\MbMaxValidator;
use Zeaven\EasySuit\Validators\MobileValidator;
use Zeaven\EasySuit\Validators\ValidatorExtension;
use Illuminate\Support\ServiceProvider;

class ValidatorServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        ValidatorExtension::add(MbMaxValidator::class);
        ValidatorExtension::add(IdCardValidator::class);
        ValidatorExtension::add(MobileValidator::class);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }
}
