<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'section_id',
        'title',
        'description',
        'sort_order',
        'is_preview',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_preview' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(CourseMaterial::class, 'lesson_id');
    }
}
