<?php

namespace App\Jobs;

use App\Models\LibraryItem;
use App\Services\AI\LibraryItemAnalyzer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLibraryItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(
        public LibraryItem $libraryItem,
    ) {}

    public function handle(LibraryItemAnalyzer $analyzer): void
    {
        try {
            $analyzer->process($this->libraryItem);
        } catch (\Throwable $e) {
            $this->libraryItem->update([
                'processing_status' => 'failed',
                'processing_error' => $e->getMessage(),
            ]);

            Log::error('Library item processing failed', [
                'item_id' => $this->libraryItem->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
