<?php

namespace Database\Factories;

use App\Enums\AccreditationStatus;
use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use App\Models\OfficialAccreditation;
use App\Models\TechnicalOfficial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficialAccreditation>
 */
class OfficialAccreditationFactory extends Factory
{
    protected $model = OfficialAccreditation::class;

    public function definition(): array
    {
        return [
            'technical_official_id' => TechnicalOfficial::factory(),
            'category' => OfficialCategory::Timekeeper,
            'grade' => OfficialGrade::Provincial,
            'status' => AccreditationStatus::Active,
            'learner_sessions_completed' => 6,
            'clinic_attended_on' => now()->subYear()->toDateString(),
            'exam_passed_on' => now()->subYear()->toDateString(),
            'attained_on' => now()->subYear()->toDateString(),
            'expires_on' => now()->addYear()->toDateString(),
        ];
    }
}
