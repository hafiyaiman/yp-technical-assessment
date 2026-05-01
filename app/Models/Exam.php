<?php

namespace App\Models;

use App\Enums\ExamStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'lecturer_id',
        'school_class_id',
        'subject_id',
        'title',
        'instructions',
        'duration_minutes',
        'available_from',
        'available_until',
        'status',
        'published_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExamStatus::class,
            'available_from' => 'datetime',
            'available_until' => 'datetime',
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ExamStatus::Published->value);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $query): void {
                $query->whereNull('available_from')->orWhere('available_from', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('available_until')->orWhere('available_until', '>=', now());
            });
    }

    public function scopeVisibleToStudent(Builder $query, User $student): Builder
    {
        return $query
            ->published()
            ->available()
            ->where('school_class_id', $student->school_class_id);
    }

    public function isPublished(): bool
    {
        return $this->status === ExamStatus::Published;
    }

    public function isAvailable(): bool
    {
        return ($this->available_from === null || $this->available_from->lte(now()))
            && ($this->available_until === null || $this->available_until->gte(now()));
    }

    public function canBeTakenBy(User $student): bool
    {
        return $student->school_class_id !== null
            && $this->school_class_id === $student->school_class_id
            && $this->isPublished()
            && $this->isAvailable();
    }
}
