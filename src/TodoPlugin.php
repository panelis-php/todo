<?php

namespace Panelis\Todo;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Panelis\Todo\Resources\TodoResource;

class TodoPlugin implements Plugin
{
    private string $name = 'todo';

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return $this->name;
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                TodoResource::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
