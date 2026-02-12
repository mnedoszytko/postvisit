<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Services\AI\MedicalExplainer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExplainController extends Controller
{
    public function __construct(
        private MedicalExplainer $explainer,
    ) {}

    public function explain(Request $request, Visit $visit): StreamedResponse
    {
        $validated = $request->validate([
            'term' => ['required', 'string', 'max:500'],
            'context' => ['nullable', 'string', 'max:2000'],
        ]);

        $visit->load(['patient', 'practitioner', 'visitNote', 'observations', 'conditions', 'prescriptions.medication', 'transcript']);

        set_time_limit(0);

        // Release session lock before streaming — prevents blocking concurrent requests
        session()->save();

        return response()->stream(function () use ($visit, $validated) {
            ignore_user_abort(true);

            if (! headers_sent()) {
                while (ob_get_level() > 0) {
                    ob_end_flush();
                }
                @ini_set('zlib.output_compression', '0');
                @ini_set('implicit_flush', '1');
            }

            try {
                foreach ($this->explainer->explain($visit, $validated['term'], $validated['context'] ?? null) as $chunk) {
                    echo 'data: '.json_encode(['text' => $chunk])."\n\n";
                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }
            } catch (\Throwable $e) {
                echo 'data: '.json_encode(['text' => 'Unable to generate explanation at this time. Please try again.', 'error' => true])."\n\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();

                \Illuminate\Support\Facades\Log::error('Explain AI error', [
                    'visit_id' => $visit->id,
                    'term' => $validated['term'],
                    'error' => $e->getMessage(),
                ]);
            }

            echo "data: [DONE]\n\n";
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'Content-Encoding' => 'none',
        ]);
    }
}
