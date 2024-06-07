<?php

namespace Panelis\Todo\Enums;

use App\Models\Enums\HasOption;

enum TodoPriority: string implements HasOption
{
    case Low = 'low';

    case Medium = 'medium';

    case High = 'high';

    public static function options(): array
    {
        return collect(TodoPriority::cases())
            ->mapWithKeys(fn (TodoPriority $case): array => [$case->value => $case->label()])
            ->toArray();
    }

    public function label(): string
    {
        return __(sprintf('todo.priority_%s', $this->value));
    }
}
