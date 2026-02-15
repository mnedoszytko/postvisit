<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Services\AI\PatientEducationGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EducationController extends Controller
{
    public function __construct(
        private PatientEducationGenerator $generator,
    ) {}

    /**
     * Generate a comprehensive patient education document for a visit.
     *
     * Streams the document via SSE, leveraging Opus 4.6's 128K output capacity.
     */
    public function generate(Request $request, Visit $visit): StreamedResponse
    {
        // Eager load relationships needed for context
        $visit->loadMissing([
            'patient', 'practitioner', 'visitNote', 'transcript',
            'observations', 'conditions', 'prescriptions',
            'prescriptions.medication',
        ]);

        // AI streaming can take >60s with extended thinking + long output
        set_time_limit(0);

        // Release session lock before streaming — prevents blocking concurrent requests
        if (session()->isStarted()) {
            session()->save();
        }

        return new StreamedResponse(function () use ($visit) {
            // Allow streaming to continue even if client disconnects
            ignore_user_abort(true);

            // Disable PHP output buffering for true SSE streaming
            if (! headers_sent()) {
                while (ob_get_level() > 0) {
                    ob_end_flush();
                }
                @ini_set('zlib.output_compression', '0');
                @ini_set('implicit_flush', '1');
            }

            try {
                foreach ($this->generator->generate($visit) as $chunk) {
                    if (is_array($chunk)) {
                        $type = $chunk['type'] ?? 'text';
                        $content = $chunk['content'] ?? '';

                        if ($type === 'status') {
                            echo 'data: '.json_encode(['status' => $content])."\n\n";
                        } elseif ($type === 'thinking') {
                            echo 'data: '.json_encode(['thinking' => $content])."\n\n";
                        } elseif ($type === 'tool_use') {
                            echo 'data: '.json_encode(['tool_use' => json_decode($content, true)])."\n\n";
                        } else {
                            echo 'data: '.json_encode(['text' => $content])."\n\n";
                        }
                    } else {
                        echo 'data: '.json_encode(['text' => $chunk])."\n\n";
                    }

                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }
            } catch (\Throwable $e) {
                Log::error('Education generation failed', [
                    'visit_id' => $visit->id,
                    'error' => $e->getMessage(),
                ]);
                echo 'data: '.json_encode(['error' => 'Failed to generate education document. Please try again.'])."\n\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
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
