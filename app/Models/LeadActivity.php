<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'lead_id',
        'contact_id',
        'platform',
        'activity_type',
        'direction',
        'platform_message_id',
        'message_text',
        'payload',
        'happened_at',
        'created_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'happened_at' => 'datetime',
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
