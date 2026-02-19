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
        return [
            'user_id' => null,
            'title' => $this->faker->sentence(3),
            'url' => $this->faker->url(),
            'code' => Str::random(8),
        ];
    }
}
