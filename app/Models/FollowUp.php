<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'lead_id',
        'contact_id',
        'trigger_type',
        'stage_snapshot',
        'status',
        'due_at',
        'completed_at',
        'summary',
        'metadata',
        'assigned_to_user_id',
        'created_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'lead',
            function (Builder $leadQuery) use ($user): Builder {
                $leadQuery->whereNull($leadQuery->getModel()->qualifyColumn('deleted_at'));

                return $user->isAdmin()
                    ? $leadQuery
                    : $leadQuery->visibleTo($user);
            }
        );
    }
}
