<?php

namespace Panelis\Todo\Enums;

use App\Models\Enums\HasOption;

enum TodoStatus: string implements HasOption
{
    case New = 'new';

    case InProgress = 'in-progress';

    case Pending = 'pending';

    case Completed = 'completed';

    case Archived = 'archived';

    public static function options(): array
    {
        return collect(TodoStatus::cases())
            ->mapWithKeys(fn (TodoStatus $case): array => [$case->value => $case->label()])
            ->toArray();
    }

    public function label(): string
    {
        return __(sprintf('todo.status_%s', $this->value));
    }
}
