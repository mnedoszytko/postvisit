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

        return $this->intendedModel();
    }

    /**
     * The intended model ID for this tier (ignores env override).
     */
    public function intendedModel(): string
    {
        return match ($this) {
            self::Good => 'claude-sonnet-4-5-20250929',
            self::Better, self::Opus46 => 'claude-opus-4-6',
        };
    }

    /**
     * Human-readable model name for UI display.
     */
    public function displayModel(): string
    {
        return match ($this) {
            self::Good => 'Claude Sonnet 4.5',
            self::Better, self::Opus46 => 'Claude Opus 4.6',
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
