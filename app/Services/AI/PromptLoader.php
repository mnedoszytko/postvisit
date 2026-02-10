<?php

namespace App\Services\AI;

use RuntimeException;

class PromptLoader
{
    private string $basePath;

    public function __construct()
    {
        $this->basePath = base_path('prompts');
    }

    /**
     * Load a prompt file by name.
     *
     * @param string $promptName Name without extension (e.g., 'qa-assistant')
     * @return string Raw markdown content
     * @throws RuntimeException If the prompt file does not exist
     */
    public function load(string $promptName): string
    {
        $path = $this->basePath . '/' . $promptName . '.md';

        if (! file_exists($path)) {
            throw new RuntimeException("Prompt file not found: {$promptName}.md");
        }

        return file_get_contents($path);
    }

    /**
     * Check if a prompt file exists.
     */
    public function exists(string $promptName): bool
    {
        return file_exists($this->basePath . '/' . $promptName . '.md');
    }

    /**
     * List all available prompt names.
     *
     * @return array<string>
     */
    public function available(): array
    {
        $files = glob($this->basePath . '/*.md');

        return array_map(
            fn (string $file) => basename($file, '.md'),
            $files ?: []
        );
    }
}
