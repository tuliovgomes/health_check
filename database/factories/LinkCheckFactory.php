<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LinkStatus;
use App\Models\Link;
use App\Models\LinkCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LinkCheck>
 */
class LinkCheckFactory extends Factory
{
    protected $model = LinkCheck::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'link_id' => Link::factory(),
            'status' => fake()->randomElement(LinkStatus::cases()),
            'http_status' => fake()->randomElement([200, 201, 204, 301, 302, 400, 404, 500, 503, null]),
            'response_time_ms' => fake()->numberBetween(50, 5000),
            'error' => fake()->optional(0.2)->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the check was successful (up).
     */
    public function up(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LinkStatus::UP,
            'http_status' => 200,
            'error' => null,
        ]);
    }

    /**
     * Indicate that the check failed (down).
     */
    public function down(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LinkStatus::DOWN,
            'http_status' => fake()->randomElement([500, 503, null]),
            'error' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the check was slow/unhealthy.
     */
    public function slow(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LinkStatus::UNHEALTH,
            'http_status' => 200,
            'response_time_ms' => fake()->numberBetween(3000, 10000),
            'error' => null,
        ]);
    }
}
