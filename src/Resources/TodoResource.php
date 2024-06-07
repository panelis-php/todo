<?php

namespace Panelis\Todo\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Panelis\Todo\Enums\TodoPriority;
use Panelis\Todo\Enums\TodoStatus;
use Panelis\Todo\Models\Todo;
use Panelis\Todo\Pages\ManageTodos;

class TodoResource extends Resource
{
    protected static ?string $model = Todo::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    public static function isScopedToTenant(): bool
    {
        return false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.misc');
    }

    public static function getLabel(): ?string
    {
        return __('todo.todo');
    }

    public static function getActiveNavigationIcon(): ?string
    {
        return 'heroicon-s-queue-list';
    }

    public static function getNavigationBadge(): ?string
    {
        return Todo::query()->whereIn('status', ['new', 'pending', 'in progress'])
            ->whereRelation('users', 'user_id', Auth::id())
            ->count();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (Auth::user()->can('View todo') || Auth::user()->can('View all todos'))
            && config('modules.todo');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('todo.title'))
                    ->columnSpanFull()
                    ->required()
                    ->minLength(3)
                    ->maxLength(250),

                Textarea::make('description')
                    ->label(__('todo.description'))
                    ->columnSpanFull()
                    ->maxLength(250),

                Select::make('user_id')
                    ->label(__('todo.user'))
                    ->columnSpanFull()
                    ->relationship('users', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('status')
                    ->label(__('todo.status'))
                    ->default('new')
                    ->searchable()
                    ->options(TodoStatus::options())
                    ->required(),

                Select::make('priority')
                    ->label(__('todo.priority'))
                    ->default('medium')
                    ->options(TodoPriority::options())
                    ->searchable()
                    ->required(),

                DateTimePicker::make('due_at')
                    ->label(__('todo.due_at'))
                    ->columnSpanFull()
                    ->seconds(false)
                    ->native(false)
                    ->minutesStep(10)
                    ->hoursStep(2)
                    ->minDate(now())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $canUpdate = Auth::user()->can('Update todo');
        $canDelete = Auth::user()->can('Delete todo');

        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->when(! Auth::user()->can('View all todos'), function (Builder $query): Builder {
                    return $query->whereHas('users', function (Builder $user): Builder {
                        return $user->whereUserId(Auth::id());
                    });
                });
            })
            ->defaultGroup('priority')
            ->groups([
                Group::make('priority')
                    ->label(__('todo.priority'))
                    ->getTitleFromRecordUsing(fn (Todo $todo): string => $todo->priority->label())
                    ->collapsible(),

                Group::make('status')
                    ->label(__('todo.status'))
                    ->getTitleFromRecordUsing(fn (Todo $todo): string => $todo->status->label())
                    ->collapsible(),
            ])
            ->columns([
                TextColumn::make('status')
                    ->label(__('todo.status'))
                    ->sortable()
                    ->formatStateUsing(fn (Todo $todo): string => $todo->status->value)
                    ->badge()
                    ->color(fn (Todo $todo): string => match ($todo->status->value) {
                        'new' => 'primary',
                        'pending' => 'warning',
                        'in progress' => 'info',
                        'completed' => 'success',
                        'archived' => 'gray',
                    }),

                TextColumn::make('name')
                    ->label(__('todo.title'))
                    ->searchable()
                    ->description(fn (?Model $model): string => $model->description ?? '')
                    ->formatStateUsing(function (Todo $todo, string $state): string {
                        if ($todo->status === TodoStatus::Completed) {
                            return sprintf('~~%s~~', $state);
                        }

                        return $state;
                    })
                    ->markdown(),

                TextColumn::make('users.name')
                    ->limitList(2)
                    ->label(__('todo.user')),

                TextColumn::make('due_at')
                    ->label(__('todo.due_at'))
                    ->sortable()
                    ->date(
                        config('app.datetime_format', 'Y-m-d H:i:s'),
                        config('app.datetime_timezo', config('app.timezone')),
                    ),

                TextColumn::make('priority')
                    ->label(__('todo.priority'))
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn (Todo $todo): string => $todo->priority->value)
                    ->color(fn (Todo $todo): string => match ($todo->priority->value) {
                        'low' => 'success',
                        'medium' => 'warning',
                        'high' => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('todo.status'))
                    ->options(TodoStatus::options())
                    ->searchable()
                    ->multiple(),

                SelectFilter::make('priority')
                    ->label(__('todo.priority'))
                    ->translateLabel()
                    ->options(TodoPriority::options())
                    ->searchable()
                    ->multiple(),

                SelectFilter::make('users')
                    ->label(__('todo.user'))
                    ->relationship('users', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('complete')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->disabled(function (Todo $todo): bool {
                        return $todo->status === TodoStatus::Completed
                            || ! in_array(Auth::id(), $todo->users->pluck('id')->toArray());
                    })
                    ->action(function (Todo $todo): void {
                        try {
                            $todo->status = TodoStatus::Completed;
                            $todo->save();

                            Notification::make('completed')
                                ->title(__('todo.marked_as_completed'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Log::error($e);
                        }
                    }),
                ActionGroup::make([
                    EditAction::make()->visible($canUpdate),
                    DeleteAction::make()->visible($canDelete),
                    ForceDeleteAction::make()->visible($canDelete),
                    RestoreAction::make()->visible($canUpdate),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTodos::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
