<?php

namespace App\Models;

use App\Enums\MaterialType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_id',
        'lesson_id',
        'title',
        'description',
        'type',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => MaterialType::class,
            'file_size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }
}
