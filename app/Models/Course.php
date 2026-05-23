<?php

namespace App\Models;

use App\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'mentor_id',
        'category_id',
        'title',
        'slug',
        'short_description',
        'description',
        'price',
        'thumbnail_path',
        'status',
        'published_at',
        'archived_at',
        'deleted_by_mentor_at',
        'hidden_by_admin_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'status' => CourseStatus::class,
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'deleted_by_mentor_at' => 'datetime',
            'hidden_by_admin_at' => 'datetime',
        ];
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CourseSection::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(CourseMaterial::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }

    public function publishedReviews(): HasMany
    {
        return $this->reviews()->where('is_published', true);
    }

    public function isPublished(): bool
    {
        return $this->status === CourseStatus::Published;
    }
}
