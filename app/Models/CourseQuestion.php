<?php

namespace App\Models;

use App\Enums\QuestionType;
use Carbon\CarbonImmutable;
use Database\Factories\CourseQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $course_id
 * @property int|null $course_lesson_id
 * @property string $prompt
 * @property QuestionType $type
 * @property string|null $explanation
 * @property string|null $rule_reference
 * @property bool $in_exam_bank
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class CourseQuestion extends Model
{
    /** @use HasFactory<CourseQuestionFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'in_exam_bank' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<CourseLesson, $this> */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'course_lesson_id');
    }

    /** @return HasMany<CourseQuestionOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(CourseQuestionOption::class)->orderBy('position');
    }

    /**
     * @return array<int, int>
     */
    public function correctOptionIds(): array
    {
        return $this->options->where('is_correct', true)->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * An answer is right only when it selects every correct option and nothing
     * else, so a multiple-answer question cannot be passed by ticking them all.
     *
     * @param  array<int, int>  $selected
     */
    public function isAnsweredCorrectlyBy(array $selected): bool
    {
        $correct = $this->correctOptionIds();
        $given = array_values(array_unique(array_map('intval', $selected)));

        sort($correct);
        sort($given);

        return $correct !== [] && $correct === $given;
    }
}
