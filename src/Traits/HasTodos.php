<?php

namespace Panelis\Todo\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Panelis\Todo\Models\Todo;

trait HasTodos
{
    public function todos(): BelongsToMany
    {
        return $this->belongsToMany(Todo::class);
    }
}
