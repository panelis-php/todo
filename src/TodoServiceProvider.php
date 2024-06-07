<?php

namespace Panelis\Todo;

use Illuminate\Support\ServiceProvider;
use Spatie\LaravelPackageTools\Package;

class TodoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(realpath(__DIR__.'/../database/migrations'));
    }
}
