<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Context Compaction (Longitudinal Memory)
    |--------------------------------------------------------------------------
    |
    | When enabled, completed chat sessions (5+ messages) are summarized by AI
    | and loaded as additional context in future sessions for the same patient.
    | Disabled by default — opt-in via environment variable.
    |
    */
    'context_compaction_enabled' => env('CONTEXT_COMPACTION_ENABLED', false),

];
