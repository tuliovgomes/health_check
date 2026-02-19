<?php

namespace Database\Factories;

use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LinkFactory extends Factory
{
    protected $model = Link::class;

    public function definition(): array
    {
        $intervals = [1, 5, 15, 30, 60];

        return [
            'user_id' => null,
            'title' => $this->faker->sentence(3),
            'url' => $this->faker->url(),
            'code' => Str::random(8),
            'check_interval' => $this->faker->randomElement($intervals),
            'last_checked_at' => null,
        ];
    }
}
