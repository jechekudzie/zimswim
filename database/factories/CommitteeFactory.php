<?php

namespace Database\Factories;

use App\Models\Committee;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Committee>
 */
class CommitteeFactory extends Factory
{
    protected $model = Committee::class;

    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word().' Committee');

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'kind' => 'committee',
            'position' => 0,
        ];
    }
}
