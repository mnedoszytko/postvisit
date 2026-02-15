<?php

namespace App\Enums;

enum AiTier: string
{
    case Good = 'good';
    case Better = 'better';
    case Opus46 = 'opus46';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'Standard AI',
            self::Better => 'Enhanced AI',
            self::Opus46 => 'Opus 4.6 Clinical Intelligence',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Good => 'Good',
            self::Better => 'Better',
            self::Opus46 => 'Opus 4.6',
        };
    }

    public function model(): string
    {
        $override = config('anthropic.default_model');

        // When ANTHROPIC_MODEL env is set, all tiers use that model (dev cost savings)
        if ($override && $override !== 'claude-opus-4-6') {
            return $override;
        }

        return match ($this) {
            self::Good => 'claude-sonnet-4-5-20250929',
            self::Better, self::Opus46 => 'claude-opus-4-6',
        };
    }

    public function thinkingEnabled(): bool
    {
        return match ($this) {
            self::Good => false,
            self::Better, self::Opus46 => true,
        };
    }

    /**
     * Thinking budget tokens per AI subsystem.
     */
    public function thinkingBudget(string $subsystem): int
    {
        return match ($this) {
            self::Good => 0,
            self::Better => match ($subsystem) {
                'chat' => 4000,
                'scribe' => 6000,
                'escalation' => 0,
                'reasoning' => 6000,
                'library' => 6000,
                default => 4000,
            },
            self::Opus46 => match ($subsystem) {
                'chat' => 8000,
                'scribe' => 10000,
                'escalation' => 6000,
                'reasoning' => 10000,
                'library' => 10000,
                default => 8000,
            },
        };
    }

    /**
     * Thinking budget tokens scaled by effort level for adaptive reasoning.
     *
     * Opus 4.6 supports variable budget_tokens per request, allowing
     * simple questions to get fast 1K-token thinking while safety-critical
     * questions get deep 16K-token reasoning.
     *
     * @return array{budget_tokens: int, max_tokens: int}
     */
    public function thinkingBudgetForEffort(string $effort): array
    {
        return match ($this) {
            self::Good => ['budget_tokens' => 0, 'max_tokens' => 4096],
            self::Better => match ($effort) {
                'low' => ['budget_tokens' => 512, 'max_tokens' => 2048],
                'high' => ['budget_tokens' => 4000, 'max_tokens' => 8000],
                'max' => ['budget_tokens' => 8000, 'max_tokens' => 16000],
                default => ['budget_tokens' => 2000, 'max_tokens' => 4000], // medium
            },
            self::Opus46 => match ($effort) {
                'low' => ['budget_tokens' => 1024, 'max_tokens' => 4096],
                'high' => ['budget_tokens' => 8000, 'max_tokens' => 16000],
                'max' => ['budget_tokens' => 16000, 'max_tokens' => 32000],
                default => ['budget_tokens' => 4000, 'max_tokens' => 8000], // medium
            },
        };
    }

    public function escalationThinkingEnabled(): bool
    {
        return $this === self::Opus46;
    }

    public function cachingEnabled(): bool
    {
        return match ($this) {
            self::Good => false,
            self::Better, self::Opus46 => true,
        };
    }

    public function guidelinesEnabled(): bool
    {
        return $this === self::Opus46;
    }

    /**
     * Feature summary for the UI.
     *
     * @return string[]
     */
    public function features(): array
    {
        return match ($this) {
            self::Good => [
                'Sonnet model for fast responses',
                'Keyword-based safety detection',
                'Basic visit context',
            ],
            self::Better => [
                'Opus model for deeper understanding',
                'Extended thinking on chat responses',
                'Prompt caching for speed',
            ],
            self::Opus46 => [
                'Opus with full extended thinking',
                'Multi-step clinical reasoning (Plan-Execute-Verify)',
                'Clinical reasoning on safety decisions',
                'Real clinical guidelines in context',
                'Prompt caching across all calls',
            ],
        };
    }
}
