<?php

namespace App\Services\AI;

use App\Enums\AiTier;
use Illuminate\Support\Facades\Cache;

class AiTierManager
{
    private const CACHE_KEY = 'ai_tier';

    public function current(): AiTier
    {
        $envTier = config('anthropic.tier');
        if ($envTier) {
            return AiTier::tryFrom($envTier) ?? AiTier::Opus46;
        }

        $value = Cache::get(self::CACHE_KEY, 'opus46');

        return AiTier::tryFrom($value) ?? AiTier::Opus46;
    }

    public function set(AiTier $tier): void
    {
        Cache::forever(self::CACHE_KEY, $tier->value);
    }

    /**
     * Get all tiers with their configuration for the API.
     *
     * @return array<int, array{value: string, label: string, short_label: string, model: string, features: string[], active: bool}>
     */
    public function allTiers(): array
    {
        $current = $this->current();

        return array_map(fn (AiTier $tier) => [
            'value' => $tier->value,
            'label' => $tier->label(),
            'short_label' => $tier->shortLabel(),
            'model' => $tier->model(),
            'features' => $tier->features(),
            'active' => $tier === $current,
        ], AiTier::cases());
    }
}
