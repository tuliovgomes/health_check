<?php

namespace App\Enums;

enum Plan: string
{
    case FREE = 'free';
    case STARTER = 'starter';
    case UNLIMITED = 'unlimited';

    public function linksQuota(): ?int
    {
        return config('plans.plans')[$this->value]['links_quota'] ?? null;
    }

    public function price(): float
    {
        return (float) (config('plans.plans')[$this->value]['price'] ?? 0);
    }

    public function displayName(): string
    {
        return config('plans.plans')[$this->value]['name'] ?? ucfirst($this->value);
    }
}
