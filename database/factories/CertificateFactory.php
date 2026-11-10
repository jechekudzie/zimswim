<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'verification_code' => 'ZSW-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)),
            'user_id' => User::factory(),
            'holder_name' => fake()->name(),
            'title' => 'Timekeeper',
            'issued_on' => now()->toDateString(),
            'expires_on' => now()->addYears(2)->toDateString(),
            'status' => 'valid',
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_on' => now()->subDay()->toDateString(),
        ]);
    }
}
