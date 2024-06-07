<?php

namespace Panelis\Todo\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Panelis\Todo\Resources\TodoResource;

class ManageTodos extends ManageRecords
{
    protected static string $resource = TodoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(Auth::user()->can('Create todo')),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function mount(): void
    {
        abort_unless(config('modules.todo'), Response::HTTP_NOT_FOUND);
        abort_unless(
            Auth::user()->can('View todo') || Auth::user()->can('View all todo'),
            Response::HTTP_FORBIDDEN,
        );
    }
}
