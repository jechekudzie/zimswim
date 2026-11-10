<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CourseLessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $course_id
 * @property string $title
 * @property string $slug
 * @property int $position
 * @property string|null $summary
 * @property string|null $content
 * @property string|null $video_url
 * @property int|null $video_seconds
 * @property string|null $attachment_path
 * @property bool $is_published
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class CourseLesson extends Model
{
    /** @use HasFactory<CourseLessonFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return HasMany<CourseQuestion, $this> */
    public function quiz(): HasMany
    {
        return $this->hasMany(CourseQuestion::class)->where('is_active', true);
    }

    public function runtime(): ?string
    {
        if ($this->video_seconds === null) {
            return null;
        }

        return sprintf('%d:%02d', intdiv($this->video_seconds, 60), $this->video_seconds % 60);
    }
}
