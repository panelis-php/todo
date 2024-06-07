<?php

namespace Panelis\Todo\Models;

use App\Models\Traits\HasUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Panelis\Todo\Enums\TodoPriority;
use Panelis\Todo\Enums\TodoStatus;

/**
 * @property TodoPriority $priority
 * @property TodoStatus $status
 * @property Carbon $due_at
 */
class Todo extends Model
{
    use HasFactory;
    use HasUser;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'due_at',
        'status',
        'priority',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'priority' => TodoPriority::class,
        'status' => TodoStatus::class,
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
