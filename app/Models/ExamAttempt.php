<?php

namespace App\Models;

use App\Enums\ExamAttemptStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'status',
        'started_at',
        'expires_at',
        'submitted_at',
        'graded_at',
        'score',
        'max_score',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExamAttemptStatus::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === ExamAttemptStatus::InProgress && ! $this->isExpired();
    }

    public function getPercentageAttribute(): float
    {
        return $this->max_score > 0 ? round(($this->score / $this->max_score) * 100, 1) : 0;
    }

    public function getPercentageColorAttribute(): string
    {
        return match (true) {
            $this->percentage >= 80 => 'green',
            $this->percentage >= 60 => 'blue',
            $this->percentage >= 40 => 'yellow',
            default => 'red',
        };
    }
}
