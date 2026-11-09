<?php

namespace App\Models;

use App\ProjectBudget;
use App\ProjectStatus;
use App\ProjectUrgency;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use CentralConnection, HasFactory;

    protected $fillable = [
        'homeowner_id',
        'subcategory_id',
        'title',
        'description',
        'city',
        'state',
        'zip',
        'urgency',
        'budget',
        'status',
        'is_hidden',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'urgency' => ProjectUrgency::class,
            'budget' => ProjectBudget::class,
            'status' => ProjectStatus::class,
            'is_hidden' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Homeowner, $this>
     */
    public function homeowner(): BelongsTo
    {
        return $this->belongsTo(Homeowner::class);
    }

    /**
     * @return BelongsTo<Subcategory, $this>
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    /**
     * @return HasMany<ProjectInterest, $this>
     */
    public function interests(): HasMany
    {
        return $this->hasMany(ProjectInterest::class);
    }

    /**
     * Projects contractors can browse: open and not hidden by the admin.
     *
     * @param  Builder<Project>  $query
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('status', ProjectStatus::Open)->where('is_hidden', false);
    }
}
