<?php

namespace App\Http\Controllers\Api;

use App\Enums\AiTier;
use App\Http\Controllers\Controller;
use App\Services\AI\AiTierManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private AiTierManager $tierManager,
    ) {}

    /**
     * Get current AI tier and all available tiers.
     */
    public function getAiTier(): JsonResponse
    {
        return response()->json([
            'data' => [
                'current' => $this->tierManager->current()->value,
                'tiers' => $this->tierManager->allTiers(),
            ],
        ]);
    }

    /**
     * Set the AI tier.
     */
    public function setAiTier(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tier' => ['required', 'string', 'in:good,better,opus46'],
        ]);

        $tier = AiTier::from($validated['tier']);
        $this->tierManager->set($tier);

        return response()->json([
            'data' => [
                'current' => $tier->value,
                'label' => $tier->label(),
                'message' => "AI tier switched to {$tier->shortLabel()}",
            ],
        ]);
    }
}
