<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // SPA root requires a built Vite manifest; use the health endpoint for a stable 200 in tests.
        $response = $this->get('/up');

        $response->assertStatus(200);
    }
}
